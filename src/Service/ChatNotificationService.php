<?php

namespace App\Service;

use App\Config\WebhookScheme;
use App\Entity\WatchList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

readonly class ChatNotificationService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function sendChatNotification(WatchList $watchList, ChatNotificationInterface $notification): void
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
                        $this->logger->info('Chat message sent with {schema} for Watchlist {token}',
                            [
                                'scheme' => $webhookScheme->name,
                                'token' => $watchList->getToken(),
                            ]);
                    } catch (\Throwable) {
                        $this->logger->error('Unable to send a chat message to {scheme} for Watchlist {token}',
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
