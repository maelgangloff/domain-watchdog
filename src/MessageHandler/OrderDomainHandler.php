<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\OrderDomain;
use App\Notifier\DomainOrderErrorNotification;
use App\Notifier\DomainOrderNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use App\Service\ChatNotificationService;
use App\Service\Connector\AbstractProvider;
use App\Service\InfluxdbService;
use App\Service\StatService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;

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
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private StatService $statService,
        private ChatNotificationService $chatNotificationService,
        private InfluxdbService $influxdbService,
        #[Autowire(service: 'service_container')]
        private ContainerInterface $locator,
        #[Autowire(param: 'influxdb_enabled')]
        private bool $influxdbEnabled,
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

        /*
         * We make sure that the domain name is marked absent from WHOIS in the database before continuing.
         * A connector must also be associated with the Watchlist to allow the purchase of the domain name.
         * Otherwise, we do nothing.
         */

        if (null === $connector || !$domain->getDeleted()) {
            return;
        }

        $this->logger->notice('Watchlist {watchlist} is linked to connector {connector}. A purchase attempt will be made for domain name {ldhName} with provider {provider}.', [
            'watchlist' => $message->watchListToken,
            'connector' => $connector->getId(),
            'ldhName' => $message->ldhName,
            'provider' => $connector->getProvider()->value,
        ]);

        try {
            $provider = $connector->getProvider();

            if (null === $provider) {
                throw new \InvalidArgumentException('Provider not found');
            }

            $connectorProviderClass = $provider->getConnectorProvider();
            /** @var AbstractProvider $connectorProvider */
            $connectorProvider = $this->locator->get($connectorProviderClass);

            /*
             * The user is authenticated to ensure that the credentials are still valid.
             * If no errors occur, the purchase is attempted.
             */

            $connectorProvider->authenticate($connector->getAuthData());

            $connectorProvider->orderDomain($domain, $this->kernel->isDebug());

            /*
             * If the purchase was successful, the statistics are updated and a success message is sent to the user.
             */
            $this->logger->notice('Watchlist {watchlist} is linked to connector {connector}. A purchase was successfully made for domain {ldhName} with provider {provider}.', [
                'watchlist' => $message->watchListToken,
                'connector' => $connector->getId(),
                'ldhName' => $message->ldhName,
                'provider' => $connector->getProvider()->value,
            ]);

            $this->statService->incrementStat('stats.domain.purchased');
            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainOrderPoint($connector, $domain, true);
            }
            $notification = (new DomainOrderNotification($this->sender, $domain, $connector));
            $this->mailer->send($notification->asEmailMessage(new Recipient($watchList->getUser()->getEmail()))->getMessage());
            $this->chatNotificationService->sendChatNotification($watchList, $notification);
        } catch (\Throwable $exception) {
            /*
             * The purchase was not successful (for several possible reasons that we have not determined).
             * The user is informed and the exception is raised, which may allow you to try again.
             */
            $this->logger->warning('Unable to complete purchase. An error message is sent to user {username}.', [
                'username' => $watchList->getUser()->getUserIdentifier(),
            ]);

            $this->statService->incrementStat('stats.domain.purchase.failed');
            if ($this->influxdbEnabled) {
                $this->influxdbService->addDomainOrderPoint($connector, $domain, false);
            }
            $notification = (new DomainOrderErrorNotification($this->sender, $domain));
            $this->mailer->send($notification->asEmailMessage(new Recipient($watchList->getUser()->getEmail()))->getMessage());
            $this->chatNotificationService->sendChatNotification($watchList, $notification);

            throw $exception;
        }
    }
}
