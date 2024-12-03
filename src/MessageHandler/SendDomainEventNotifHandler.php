<?php

namespace App\MessageHandler;

use App\Config\TriggerAction;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\SendDomainEventNotif;
use App\Notifier\DomainUpdateNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use App\Service\ChatNotificationService;
use App\Service\StatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
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

        /*
         * For each new event whose date is after the domain name update date (before the current domain name update)
         */

        /** @var DomainEvent $event */
        foreach ($domain->getEvents()->filter(fn ($event) => $message->updatedAt < $event->getDate() && $event->getDate() < new \DateTime()) as $event) {
            $watchListTriggers = $watchList->getWatchListTriggers()
                ->filter(fn ($trigger) => $trigger->getEvent() === $event->getAction());

            /*
             * For each trigger, we perform the appropriate action: send email or send push notification (for now)
             */

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
                    $this->chatNotificationService->sendChatNotification($watchList, $notification);
                }

                $this->statService->incrementStat('stats.alert.sent');
            }
        }
    }
}
