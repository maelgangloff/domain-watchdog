<?php

namespace App\MessageHandler;

use App\Config\Connector\OvhConnector;
use App\Config\ConnectorProvider;
use App\Config\TriggerAction;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\ProcessDomainTrigger;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use Exception;
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
        private string              $mailerSenderEmail,
        private MailerInterface     $mailer,
        private WatchListRepository $watchListRepository,
        private DomainRepository    $domainRepository,
        private KernelInterface     $kernel

    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function __invoke(ProcessDomainTrigger $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(["token" => $message->watchListToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(["ldhName" => $message->ldhName]);

        /** @var DomainEvent $event */
        foreach ($domain->getEvents()->filter(fn($event) => $message->updatedAt < $event->getDate()) as $event) {
            $watchListTriggers = $watchList->getWatchListTriggers()
                ->filter(fn($trigger) => $trigger->getEvent() === $event->getAction());

            /** @var WatchListTrigger $watchListTrigger */
            foreach ($watchListTriggers->getIterator() as $watchListTrigger) {

                switch ($watchListTrigger->getAction()) {
                    case TriggerAction::SendEmail:
                        $this->sendEmailDomainUpdated($event, $watchList->getUser());
                        break;
                    case TriggerAction::BuyDomain :
                        if ($watchListTrigger->getConnector() === null) throw new Exception('Connector is missing');
                        $connector = $watchListTrigger->getConnector();
                        if ($connector->getProvider() === ConnectorProvider::OVH) {
                            $ovh = new OVHConnector($connector->getAuthData());
                            $isDebug = $this->kernel->isDebug();
                            $ovh->orderDomain(
                                $domain,
                                true,
                                true,
                                true,
                                $isDebug
                            );

                        } else throw new Exception("Unknown provider");
                }
            }
        }
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
            ->htmlTemplate('emails/domain_updated.html.twig')
            ->locale('en')
            ->context([
                "event" => $domainEvent
            ]);

        $this->mailer->send($email);
    }

}
