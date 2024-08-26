<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\OrderDomain;
use App\Message\SendDomainEventNotif;
use App\Message\UpdateDomainsFromWatchlist;
use App\Notifier\DomainUpdateErrorNotification;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
final readonly class UpdateDomainsFromWatchlistHandler
{
    private Address $sender;

    public function __construct(
        private RDAPService $RDAPService,
        private MailerInterface $mailer,
        string $mailerSenderEmail,
        string $mailerSenderName,
        private MessageBusInterface $bus,
        private WatchListRepository $watchListRepository,
        private LoggerInterface $logger
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     * @throws ExceptionInterface
     */
    public function __invoke(UpdateDomainsFromWatchlist $message): void
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
                $this->RDAPService->registerDomain($domain->getLdhName());
            } catch (\Throwable $e) {
                $this->logger->error('An update error email is sent to user {username}.', [
                    'username' => $watchList->getUser()->getUserIdentifier(),
                    'error' => $e,
                ]);
                $email = (new DomainUpdateErrorNotification($this->sender, $domain))
                    ->asEmailMessage(new Recipient($watchList->getUser()->getEmail()));
                $this->mailer->send($email->getMessage());
            }

            $this->bus->dispatch(new SendDomainEventNotif($watchList->getToken(), $domain->getLdhName(), $updatedAt));

            if (null !== $watchList->getConnector() && $domain->getDeleted()) {
                $this->bus->dispatch(new OrderDomain($watchList->getToken(), $domain->getLdhName(), $updatedAt));
            }
        }
    }
}
