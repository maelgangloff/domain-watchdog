<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Domain;
use App\Entity\User;
use App\Entity\WatchList;
use App\Notifier\TestChatNotification;
use App\Repository\DomainRepository;
use App\Service\ChatNotificationService;
use App\Service\Connector\AbstractProvider;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class WatchListUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly LoggerInterface $logger,
        private readonly ChatNotificationService $chatNotificationService,
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface $locator,
    ) {
    }

    /**
     * @param WatchList $data
     *
     * @return WatchList
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $data->setUser($user);

        if ($this->parameterBag->get('limited_features')) {
            if ($data->getDomains()->count() > (int) $this->parameterBag->get('limit_max_watchlist_domains')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of domains has been reached for this Watchlist', [
                    'username' => $user->getUserIdentifier(),
                ]);

                throw new AccessDeniedHttpException('You have exceeded the maximum number of domain names allowed in this Watchlist');
            }

            $userWatchLists = $user->getWatchLists();

            /** @var Domain[] $trackedDomains */
            $trackedDomains = $userWatchLists
                ->filter(fn (WatchList $wl) => $wl->getToken() !== $data->getToken())
                ->reduce(fn (array $acc, WatchList $wl) => [...$acc, ...$wl->getDomains()->toArray()], []);

            /** @var Domain $domain */
            foreach ($data->getDomains()->getIterator() as $domain) {
                if (in_array($domain, $trackedDomains)) {
                    $ldhName = $domain->getLdhName();
                    $this->logger->notice('User {username} tried to update a watchlist with domain name {ldhName}. It is forbidden to register the same domain name twice with limited mode', [
                        'username' => $user->getUserIdentifier(),
                        'ldhName' => $ldhName,
                    ]);

                    throw new AccessDeniedHttpException("It is forbidden to register the same domain name twice in your watchlists with limited mode ($ldhName)");
                }
            }

            if (null !== $data->getWebhookDsn() && count($data->getWebhookDsn()) > (int) $this->parameterBag->get('limit_max_watchlist_webhooks')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of webhooks has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);

                throw new AccessDeniedHttpException('You have exceeded the maximum number of webhooks allowed in this Watchlist');
            }
        }

        $this->chatNotificationService->sendChatNotification($data, new TestChatNotification());

        if ($connector = $data->getConnector()) {
            if (!$user->getConnectors()->contains($connector)) {
                $this->logger->notice('The Connector ({connector}) does not belong to the user.', [
                    'username' => $user->getUserIdentifier(),
                    'connector' => $connector->getId(),
                ]);

                throw new AccessDeniedHttpException('You cannot create a Watchlist with a connector that does not belong to you');
            }

            /** @var Domain $domain */
            foreach ($data->getDomains()->getIterator() as $domain) {
                if ($domain->getDeleted()) {
                    $ldhName = $domain->getLdhName();

                    throw new BadRequestHttpException("To add a connector, no domain in this Watchlist must have already expired ($ldhName)");
                }
            }

            $connectorProviderClass = $connector->getProvider()->getConnectorProvider();
            /** @var AbstractProvider $connectorProvider */
            $connectorProvider = $this->locator->get($connectorProviderClass);

            $connectorProvider->authenticate($connector->getAuthData());
            $supported = $connectorProvider->isSupported(...$data->getDomains()->toArray());

            if (!$supported) {
                $this->logger->notice('The Connector ({connector}) does not support all TLDs in this Watchlist', [
                    'username' => $user->getUserIdentifier(),
                    'connector' => $connector->getId(),
                ]);

                throw new BadRequestHttpException('This connector does not support all TLDs in this Watchlist');
            }
        }

        $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $data;
    }
}
