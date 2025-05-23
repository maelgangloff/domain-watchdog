<?php

namespace App\Service;

use App\Config\WebhookScheme;
use App\Entity\WatchList;
use App\Notifier\DomainWatchdogNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

readonly class ChatNotificationService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function sendChatNotification(WatchList $watchList, DomainWatchdogNotification $notification): void
    {
        $webhookDsn = $watchList->getWebhookDsn();

        if (empty($webhookDsn)) {
            return;
        }

        foreach ($webhookDsn as $dsnString) {
            try {
                $dsn = new Dsn($dsnString);
            } catch (InvalidArgumentException $exception) {
                throw new BadRequestHttpException($exception->getMessage());
            }

            $scheme = $dsn->getScheme();
            $webhookScheme = WebhookScheme::tryFrom($scheme);

            if (null === $webhookScheme) {
                throw new BadRequestHttpException("The DSN scheme ($scheme) is not supported");
            }

            $transportFactoryClass = $webhookScheme->getChatTransportFactory();
            /** @var AbstractTransportFactory $transportFactory */
            $transportFactory = new $transportFactoryClass();

            $push = $notification->asPushMessage(new NoRecipient());
            $chat = $notification->asChatMessage(new NoRecipient(), $webhookScheme->value);

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
            } catch (\Throwable $exception) {
                $this->logger->error('Unable to send a chat message to {scheme} for Watchlist {token}',
                    [
                        'scheme' => $webhookScheme->name,
                        'token' => $watchList->getToken(),
                    ]);
                throw new BadRequestHttpException($exception->getMessage());
            }
        }
    }
}
