<?php

namespace App\MessageHandler;

use App\Config\Connector\OvhConnector;
use App\Config\ConnectorProvider;
use App\Config\TriggerAction;
use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\ProcessDomainTrigger;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class ProcessDomainTriggerHandler
{
    public function __construct(
        private string $mailerSenderEmail,
        private MailerInterface $mailer,
        private WatchListRepository $watchListRepository,
        private DomainRepository $domainRepository,
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function __invoke(ProcessDomainTrigger $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);

        $connector = $watchList->getConnector();
        if (null !== $connector && $domain->getDeleted()) {
            $this->logger->notice('Watchlist {watchlist} is linked to connector {connector}. A purchase attempt will be made for domain name {ldhName} with provider {provider}.', [
                'watchlist' => $message->watchListToken,
                'connector' => $connector->getId(),
                'ldhName' => $message->ldhName,
                'provider' => $connector->getProvider()->value,
            ]);
            try {
                if (ConnectorProvider::OVH === $connector->getProvider()) {
                    $ovh = new OvhConnector($connector->getAuthData());
                    $isDebug = $this->kernel->isDebug();

                    $ovh->orderDomain($domain, $isDebug);
                    $this->sendEmailDomainOrdered($domain, $connector, $watchList->getUser());
                } else {
                    throw new \Exception('Unknown provider');
                }
            } catch (\Throwable) {
                $this->logger->warning('Unable to complete purchase. An error message is sent to user {username}.', [
                    'username' => $watchList->getUser()->getUserIdentifier(),
                ]);
                $this->sendEmailDomainOrderError($domain, $watchList->getUser());
            }
        }

        /** @var DomainEvent $event */
        foreach ($domain->getEvents()->filter(fn ($event) => $message->updatedAt < $event->getDate() && $event->getDate() < new \DateTime()) as $event) {
            $watchListTriggers = $watchList->getWatchListTriggers()
                ->filter(fn ($trigger) => $trigger->getEvent() === $event->getAction());

            /** @var WatchListTrigger $watchListTrigger */
            foreach ($watchListTriggers->getIterator() as $watchListTrigger) {
                $this->logger->info('Action {event} has been detected on the domain name {ldhName}. A notification is sent to user {username}.', [
                    'event' => $event->getAction(),
                    'ldhName' => $message->ldhName,
                    'username' => $watchList->getUser()->getUserIdentifier(),
                ]);
                if (TriggerAction::SendEmail == $watchListTrigger->getAction()) {
                    $this->sendEmailDomainUpdated($event, $watchList->getUser());
                }
            }
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmailDomainOrdered(Domain $domain, Connector $connector, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerSenderEmail)
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGHEST)
            ->subject('A domain name has been ordered')
            ->htmlTemplate('emails/success/domain_ordered.html.twig')
            ->locale('en')
            ->context([
                'domain' => $domain,
                'provider' => $connector->getProvider()->value,
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmailDomainOrderError(Domain $domain, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerSenderEmail)
            ->to($user->getEmail())
            ->subject('An error occurred while ordering a domain name')
            ->htmlTemplate('emails/errors/domain_order.html.twig')
            ->locale('en')
            ->context([
                'domain' => $domain,
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmailDomainUpdated(DomainEvent $domainEvent, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerSenderEmail)
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGHEST)
            ->subject('A domain name has been changed')
            ->htmlTemplate('emails/success/domain_updated.html.twig')
            ->locale('en')
            ->context([
                'event' => $domainEvent,
            ]);

        $this->mailer->send($email);
    }
}
