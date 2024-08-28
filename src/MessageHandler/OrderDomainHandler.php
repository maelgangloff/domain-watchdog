<?php

namespace App\MessageHandler;

use App\Config\Provider\AbstractProvider;
use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\OrderDomain;
use App\Notifier\DomainOrderErrorNotification;
use App\Notifier\DomainOrderNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use App\Service\ChatNotificationService;
use App\Service\StatService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final readonly class OrderDomainHandler
{
    private Address $sender;

    public function __construct(
        string $mailerSenderEmail,
        string $mailerSenderName,
        private WatchListRepository $watchListRepository,
        private DomainRepository $domainRepository,
        private KernelInterface $kernel,
        private HttpClientInterface $client,
        private CacheItemPoolInterface $cacheItemPool,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private StatService $statService,
        private ChatNotificationService $chatNotificationService
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Symfony\Component\Notifier\Exception\TransportExceptionInterface
     * @throws \Throwable
     */
    public function __invoke(OrderDomain $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);

        $connector = $watchList->getConnector();
        if (null !== $connector && $domain->getDeleted()) {
            $this->logger->notice('Watchlist {watchlist} is linked to connector {connector}. A purchase attempt will be made for domain name {ldhName} with provider {provider}.', [
                'watchlist' => $message->watchListToken,
                'connector' => $connector->getId(),
                'ldhName' => $message->ldhName,
                'provider' => $connector->getProvider()->value,
            ]);
            try {
                $provider = $connector->getProvider();
                if (null === $provider) {
                    throw new \Exception('Provider not found');
                }

                $connectorProviderClass = $provider->getConnectorProvider();

                /** @var AbstractProvider $connectorProvider */
                $connectorProvider = new $connectorProviderClass($connector->getAuthData(), $this->client, $this->cacheItemPool, $this->kernel);

                $connectorProvider->orderDomain($domain, $this->kernel->isDebug());
                $this->statService->incrementStat('stats.domain.purchased');

                $notification = (new DomainOrderNotification($this->sender, $domain, $connector));
                $this->mailer->send($notification->asEmailMessage(new Recipient($watchList->getUser()->getEmail()))->getMessage());
                $this->chatNotificationService->sendChatNotification($watchList, $notification);
            } catch (\Throwable $exception) {
                $this->logger->warning('Unable to complete purchase. An error message is sent to user {username}.', [
                    'username' => $watchList->getUser()->getUserIdentifier(),
                ]);

                $notification = (new DomainOrderErrorNotification($this->sender, $domain));
                $this->mailer->send($notification->asEmailMessage(new Recipient($watchList->getUser()->getEmail()))->getMessage());
                $this->chatNotificationService->sendChatNotification($watchList, $notification);

                $this->statService->incrementStat('stats.domain.purchase.failed');

                throw $exception;
            }
        }
    }
}
