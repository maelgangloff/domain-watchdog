<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\Watchlist;
use App\Message\OrderDomain;
use App\Message\ProcessWatchlist;
use App\Message\UpdateDomain;
use App\Repository\WatchlistRepository;
use App\Service\Provider\AbstractProvider;
use App\Service\Provider\CheckDomainProviderInterface;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessWatchlistHandler
{
    public function __construct(
        private RDAPService $RDAPService,
        private MessageBusInterface $bus,
        private WatchlistRepository $watchlistRepository,
        private LoggerInterface $logger,
        #[Autowire(service: 'service_container')]
        private ContainerInterface $locator,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(ProcessWatchlist $message): void
    {
        /** @var Watchlist $watchlist */
        $watchlist = $this->watchlistRepository->findOneBy(['token' => $message->watchlistToken]);

        $this->logger->debug('Domain names listed in the Watchlist will be updated', [
            'watchlist' => $message->watchlistToken,
        ]);

        /** @var AbstractProvider $connectorProvider */
        $connectorProvider = $this->getConnectorProvider($watchlist);

        if ($connectorProvider instanceof CheckDomainProviderInterface) {
            $this->logger->debug('Watchlist is linked to a connector', [
                'watchlist' => $watchlist->getToken(),
                'connector' => $watchlist->getConnector()->getId(),
            ]);

            $domainList = array_unique(array_map(fn (Domain $d) => $d->getLdhName(), $watchlist->getDomains()->toArray()));

            try {
                $checkedDomains = $connectorProvider->checkDomains(...$domainList);
            } catch (\Throwable $exception) {
                $this->logger->warning('Unable to check domain names availability with this connector', [
                    'connector' => $watchlist->getConnector()->getId(),
                    'ldhName' => $domainList,
                ]);

                throw $exception;
            }

            foreach ($checkedDomains as $domain) {
                $this->bus->dispatch(new OrderDomain($watchlist->getToken(), $domain));
            }

            return;
        }

        /*
         * A domain name is updated if one or more of these conditions are met:
         * - was updated more than 7 days ago
         * - has statuses that suggest it will expire soon AND was updated more than 15 minutes ago
         * - has specific statuses that suggest there is a dispute between the registrant and the registrar AND was updated more than a day ago
         */

        /** @var Domain $domain */
        foreach ($watchlist->getDomains()->filter(fn ($domain) => $this->RDAPService->isToBeUpdated($domain, false, null !== $watchlist->getConnector())) as $domain) {
            $this->bus->dispatch(new UpdateDomain($domain->getLdhName(), $watchlist->getToken()));
        }
    }

    private function getConnectorProvider(Watchlist $watchlist): ?object
    {
        $connector = $watchlist->getConnector();
        if (null === $connector || null === $connector->getProvider()) {
            return null;
        }

        $providerClass = $connector->getProvider()->getConnectorProvider();

        return $this->locator->get($providerClass);
    }
}
