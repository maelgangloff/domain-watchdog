<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\WatchList;
use App\Message\SendDomainEventNotif;
use App\Notifier\DomainStatusUpdateNotification;
use App\Notifier\DomainUpdateNotification;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\DomainStatusRepository;
use App\Repository\WatchListRepository;
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
final readonly class SendDomainEventNotifHandler
{
    private Address $sender;

    public function __construct(
        string $mailerSenderEmail,
        string $mailerSenderName,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private StatService $statService,
        private DomainRepository $domainRepository,
        private WatchListRepository $watchListRepository,
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
    public function __invoke(SendDomainEventNotif $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);
        $recipient = new Recipient($watchList->getUser()->getEmail());

        /*
         * For each new event whose date is after the domain name update date (before the current domain name update)
         */

        /** @var DomainEvent[] $newEvents */
        $newEvents = $this->domainEventRepository->createQueryBuilder('de')
            ->select()
            ->where('de.domain = :domain')
            ->andWhere('de.date > :updatedAt')
            ->andWhere('de.date < :now')
            ->setParameter('domain', $domain)
            ->setParameter('updatedAt', $message->updatedAt)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getResult();

        foreach ($newEvents as $event) {
            if (!in_array($event->getAction(), $watchList->getTrackedEvents())) {
                continue;
            }

            $notification = new DomainUpdateNotification($this->sender, $event);

            $this->logger->info('New action has been detected on this domain name : an email is sent to user', [
                'event' => $event->getAction(),
                'ldhName' => $message->ldhName,
                'username' => $watchList->getUser()->getUserIdentifier(),
            ]);

            $this->mailer->send($notification->asEmailMessage($recipient)->getMessage());

            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainNotificationPoint($domain, 'email', true);
            }

            $webhookDsn = $watchList->getWebhookDsn();
            if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
                $this->logger->info('New action has been detected on this domain name : a notification is sent to user', [
                    'event' => $event->getAction(),
                    'ldhName' => $message->ldhName,
                    'username' => $watchList->getUser()->getUserIdentifier(),
                ]);

                $this->chatNotificationService->sendChatNotification($watchList, $notification);
                if ($this->influxdbEnabled) {
                    $this->influxdbService->addDomainNotificationPoint($domain, 'chat', true);
                }
            }

            $this->statService->incrementStat('stats.alert.sent');
        }

        /** @var DomainStatus $domainStatus */
        $domainStatus = $this->domainStatusRepository->createQueryBuilder('ds')
            ->select()
            ->where('ds.domain = :domain')
            ->andWhere('ds.date = :date')
            ->orderBy('ds.createdAt', 'DESC')
            ->setParameter('domain', $domain)
            ->setParameter('date', $message->updatedAt)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $domainStatus && count(array_intersect(
            $watchList->getTrackedEppStatus(),
            [...$domainStatus->getAddStatus(), ...$domainStatus->getDeleteStatus()]
        ))) {
            $notification = new DomainStatusUpdateNotification($this->sender, $domain, $domainStatus);

            $this->logger->info('New domain status has been detected on this domain name : an email is sent to user', [
                'addStatus' => $domainStatus->getAddStatus(),
                'deleteStatus' => $domainStatus->getDeleteStatus(),
                'status' => $domain->getStatus(),
                'ldhName' => $message->ldhName,
                'username' => $watchList->getUser()->getUserIdentifier(),
            ]);

            $this->mailer->send($notification->asEmailMessage($recipient)->getMessage());

            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainNotificationPoint($domain, 'email', true);
            }

            $webhookDsn = $watchList->getWebhookDsn();
            if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
                $this->logger->info('New domain status has been detected on this domain name : a notification is sent to user', [
                    'addStatus' => $domainStatus->getAddStatus(),
                    'deleteStatus' => $domainStatus->getDeleteStatus(),
                    'status' => $domain->getStatus(),
                    'ldhName' => $message->ldhName,
                    'username' => $watchList->getUser()->getUserIdentifier(),
                ]);

                $this->chatNotificationService->sendChatNotification($watchList, $notification);

                if ($this->influxdbEnabled) {
                    $this->influxdbService->addDomainNotificationPoint($domain, 'chat', true);
                }
            }

            $this->statService->incrementStat('stats.alert.sent');
        }
    }
}
