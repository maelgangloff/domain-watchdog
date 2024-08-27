<?php

namespace App\Service;

use App\Config\WebhookScheme;
use App\Entity\WatchList;
use App\Notifier\TestChatNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
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

                    $push = (new TestChatNotification())->asPushMessage();
                    $chat = (new TestChatNotification())->asChatMessage();

                    try {
                        $factory = $transportFactory->create($dsn);

                        if ($factory->supports($push)) {
                            $factory->send($push);
                        } elseif ($factory->supports($chat)) {
                            $factory->send($chat);
                        } else {
                            throw new BadRequestHttpException('Unsupported message type');
                        }

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
