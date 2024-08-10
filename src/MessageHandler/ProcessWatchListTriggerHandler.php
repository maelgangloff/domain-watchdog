<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\WatchList;
use App\Message\ProcessDomainTrigger;
use App\Message\ProcessWatchListTrigger;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final readonly class ProcessWatchListTriggerHandler
{
    public function __construct(
        private RDAPService $RDAPService,
        private MailerInterface $mailer,
        private string $mailerSenderEmail,
        private string $mailerSenderName,
        private MessageBusInterface $bus,
        private WatchListRepository $watchListRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessWatchListTrigger $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);

        $this->logger->info('Domain names from Watchlist {token} will be processed.', [
            'token' => $message->watchListToken,
        ]);

        /** @var Domain $domain */
        foreach ($watchList->getDomains()
                     ->filter(fn ($domain) => $domain->getUpdatedAt()
                             ->diff(
                                 new \DateTimeImmutable('now'))->days >= 7
                         || $this->RDAPService::isToBeWatchClosely($domain, $domain->getUpdatedAt())
                     ) as $domain
        ) {
            $updatedAt = $domain->getUpdatedAt();

            try {
                $domain = $this->RDAPService->registerDomain($domain->getLdhName());
            } catch (\Throwable $e) {
                $this->logger->error('An update error email is sent to user {username}.', [
                    'username' => $watchList->getUser()->getUserIdentifier(),
                    'error' => $e,
                ]);
                $this->sendEmailDomainUpdateError($domain, $watchList->getUser());
            }

            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmailDomainUpdateError(Domain $domain, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerSenderEmail, $this->mailerSenderName))
            ->to($user->getEmail())
            ->subject('An error occurred while updating a domain name')
            ->htmlTemplate('emails/errors/domain_update.html.twig')
            ->locale('en')
            ->context([
                'domain' => $domain,
            ]);

        $this->mailer->send($email);
    }
}
