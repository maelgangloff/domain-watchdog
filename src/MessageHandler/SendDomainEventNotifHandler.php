<?php

namespace App\MessageHandler;

use App\Config\TriggerAction;
use App\Config\WebhookScheme;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\SendDomainEventNotif;
use App\Notifier\DomainUpdateNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use App\Service\StatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

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
        private WatchListRepository $watchListRepository
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     * @throws ExceptionInterface
     */
    public function __invoke(SendDomainEventNotif $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);

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

                $recipient = new Recipient($watchList->getUser()->getEmail());
                $notification = new DomainUpdateNotification($this->sender, $event);

                if (TriggerAction::SendEmail == $watchListTrigger->getAction()) {
                    $this->mailer->send($notification->asEmailMessage($recipient)->getMessage());
                } elseif (TriggerAction::SendChat == $watchListTrigger->getAction()) {
                    $this->sendChatNotification($watchList, $notification, $this->logger);
                }

                $this->statService->incrementStat('stats.alert.sent');
            }
        }
    }

    /**
     * @throws \Symfony\Component\Notifier\Exception\TransportExceptionInterface
     */
    public static function sendChatNotification(WatchList $watchList, ChatNotificationInterface $notification, LoggerInterface $logger): void
    {
        $webhookDsn = $watchList->getWebhookDsn();
        if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
            foreach ($webhookDsn as $dsnString) {
                $dsn = new Dsn($dsnString);

                $scheme = $dsn->getScheme();
                $webhookScheme = WebhookScheme::tryFrom($scheme);

                if (null !== $webhookScheme) {
                    $transportFactoryClass = $webhookScheme->getChatTransportFactory();
                    /** @var AbstractTransportFactory $transportFactory */
                    $transportFactory = new $transportFactoryClass();
                    try {
                        $transportFactory->create($dsn)->send($notification->asChatMessage(new Recipient()));
                        $logger->info('Chat message sent with {schema} for Watchlist {token}',
                            [
                                'scheme' => $webhookScheme->name,
                                'token' => $watchList->getToken(),
                            ]);
                    } catch (\Throwable) {
                        $logger->error('Unable to send a chat message to {scheme} for Watchlist {token}',
                            [
                                'scheme' => $webhookScheme->name,
                                'token' => $watchList->getToken(),
                            ]);
                    }
                }
            }
        }
    }
}
