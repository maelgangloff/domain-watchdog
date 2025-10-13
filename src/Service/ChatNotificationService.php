<?php

namespace App\Service;

use App\Config\WebhookScheme;
use App\Entity\WatchList;
use App\Exception\UnsupportedDsnSchemeException;
use App\Notifier\DomainWatchdogNotification;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

readonly class ChatNotificationService
{
    public function __construct(
    ) {
    }

    /**
     * @throws UnsupportedDsnSchemeException
     * @throws TransportExceptionInterface
     */
    public function sendChatNotification(WatchList $watchList, DomainWatchdogNotification $notification): void
    {
        $webhookDsn = $watchList->getWebhookDsn();

        if (empty($webhookDsn)) {
            return;
        }

        foreach ($webhookDsn as $dsnString) {
            $dsn = new Dsn($dsnString);

            $scheme = $dsn->getScheme();
            $webhookScheme = WebhookScheme::tryFrom($scheme);

            if (null === $webhookScheme) {
                throw new UnsupportedDsnSchemeException($scheme);
            }

            $transportFactoryClass = $webhookScheme->getChatTransportFactory();
            /** @var AbstractTransportFactory $transportFactory */
            $transportFactory = new $transportFactoryClass();

            $push = $notification->asPushMessage(new NoRecipient());
            $chat = $notification->asChatMessage(new NoRecipient(), $webhookScheme->value);

            $factory = $transportFactory->create($dsn);

            if ($factory->supports($push)) {
                $factory->send($push);
            } elseif ($factory->supports($chat)) {
                $factory->send($chat);
            } else {
                throw new \InvalidArgumentException('Unsupported message type');
            }
        }
    }
}
