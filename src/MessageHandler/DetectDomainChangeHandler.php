<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\Watchlist;
use App\Message\DetectDomainChange;
use App\Notifier\DomainStatusUpdateNotification;
use App\Notifier\DomainUpdateNotification;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\DomainStatusRepository;
use App\Repository\WatchlistRepository;
use App\Service\ChatNotificationService;
use App\Service\InfluxdbService;
use App\Service\StatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
final readonly class DetectDomainChangeHandler
{
    private Address $sender;

    public function __construct(
        string $mailerSenderEmail,
        string $mailerSenderName,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private StatService $statService,
        private DomainRepository $domainRepository,
        private WatchlistRepository $watchlistRepository,
        private ChatNotificationService $chatNotificationService,
        #[Autowire(param: 'influxdb_enabled')]
        private bool $influxdbEnabled,
        private InfluxdbService $influxdbService,
        private DomainEventRepository $domainEventRepository,
        private DomainStatusRepository $domainStatusRepository,
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function __invoke(DetectDomainChange $message): void
    {
        /** @var Watchlist $watchlist */
        $watchlist = $this->watchlistRepository->findOneBy(['token' => $message->watchlistToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);
        $recipient = new Recipient($watchlist->getUser()->getEmail());

        /*
         * For each new event whose date is after the domain name update date (before the current domain name update)
         */

        /** @var DomainEvent[] $newEvents */
        $newEvents = $this->domainEventRepository->findNewDomainEvents($domain, $message->updatedAt);

        foreach ($newEvents as $event) {
            if (!in_array($event->getAction(), $watchlist->getTrackedEvents())) {
                continue;
            }

            $notification = new DomainUpdateNotification($this->sender, $event);

            $this->logger->info('New action has been detected on this domain name : an email is sent to user', [
                'event' => $event->getAction(),
                'ldhName' => $message->ldhName,
                'username' => $watchlist->getUser()->getUserIdentifier(),
            ]);

            $this->mailer->send($notification->asEmailMessage($recipient)->getMessage());

            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainNotificationPoint($domain, 'email', true);
            }

            $webhookDsn = $watchlist->getWebhookDsn();
            if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
                $this->logger->info('New action has been detected on this domain name : a notification is sent to user', [
                    'event' => $event->getAction(),
                    'ldhName' => $message->ldhName,
                    'username' => $watchlist->getUser()->getUserIdentifier(),
                ]);

                $this->chatNotificationService->sendChatNotification($watchlist, $notification);
                if ($this->influxdbEnabled) {
                    $this->influxdbService->addDomainNotificationPoint($domain, 'chat', true);
                }
            }

            $this->statService->incrementStat('stats.alert.sent');
        }

        /** @var DomainStatus $domainStatus */
        $domainStatus = $this->domainStatusRepository->findNewDomainStatus($domain, $message->updatedAt);

        if (null !== $domainStatus && count(array_intersect(
            $watchlist->getTrackedEppStatus(),
            [...$domainStatus->getAddStatus(), ...$domainStatus->getDeleteStatus()]
        ))) {
            $notification = new DomainStatusUpdateNotification($this->sender, $domain, $domainStatus);

            $this->logger->info('New domain status has been detected on this domain name : an email is sent to user', [
                'addStatus' => $domainStatus->getAddStatus(),
                'deleteStatus' => $domainStatus->getDeleteStatus(),
                'status' => $domain->getStatus(),
                'ldhName' => $message->ldhName,
                'username' => $watchlist->getUser()->getUserIdentifier(),
            ]);

            $this->mailer->send($notification->asEmailMessage($recipient)->getMessage());

            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainNotificationPoint($domain, 'email', true);
            }

            $webhookDsn = $watchlist->getWebhookDsn();
            if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
                $this->logger->info('New domain status has been detected on this domain name : a notification is sent to user', [
                    'addStatus' => $domainStatus->getAddStatus(),
                    'deleteStatus' => $domainStatus->getDeleteStatus(),
                    'status' => $domain->getStatus(),
                    'ldhName' => $message->ldhName,
                    'username' => $watchlist->getUser()->getUserIdentifier(),
                ]);

                $this->chatNotificationService->sendChatNotification($watchlist, $notification);

                if ($this->influxdbEnabled) {
                    $this->influxdbService->addDomainNotificationPoint($domain, 'chat', true);
                }
            }

            $this->statService->incrementStat('stats.alert.sent');
        }
    }
}
