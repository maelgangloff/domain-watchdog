<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\User;
use App\Entity\WatchList;
use App\Notifier\TestChatNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchListRepository;
use App\Service\ChatNotificationService;
use App\Service\Connector\AbstractProvider;
use App\Service\RDAPService;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Value\TextValue;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Exception;
use JsonException;
use Laminas\Feed\Writer\Deleted;
use Laminas\Feed\Writer\Entry;
use Laminas\Feed\Writer\Feed;
use Psr\Log\LoggerInterface;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WatchListController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface&DecoderInterface&DenormalizerInterface $serializer,
        private readonly EntityManagerInterface                                     $em,
        private readonly WatchListRepository                                        $watchListRepository,
        private readonly LoggerInterface                                            $logger,
        private readonly ChatNotificationService                                    $chatNotificationService,
        private readonly DomainRepository                                           $domainRepository,
        private readonly RDAPService                                                $RDAPService,
        private readonly RateLimiterFactory                                         $rdapRequestsLimiter,
        private readonly KernelInterface                                            $kernel,
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface                                         $locator,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws Exception
     */
    #[Route(
        path: '/api/watchlists',
        name: 'watchlist_create',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'create',
        ],
        methods: ['POST']
    )]
    public function createWatchList(Request $request): WatchList
    {
        $watchList = $this->registerDomainsInWatchlist($request->getContent(), ['watchlist:create']);

        /** @var User $user */
        $user = $this->getUser();
        $watchList->setUser($user);

        /*
         * In the limited version, we do not want a user to be able to register the same domain more than once in their watchlists.
         * This policy guarantees the equal probability of obtaining a domain name if it is requested by several users.
         */
        if ($this->getParameter('limited_features')) {
            if ($watchList->getDomains()->count() > (int)$this->getParameter('limit_max_watchlist_domains')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of domains has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of domain names allowed in this Watchlist');
            }

            $userWatchLists = $user->getWatchLists();
            if ($userWatchLists->count() >= (int)$this->getParameter('limit_max_watchlist')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of Watchlists has been reached', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of Watchlists allowed');
            }

            /** @var Domain[] $trackedDomains */
            $trackedDomains = $userWatchLists->reduce(fn(array $acc, WatchList $watchList) => [...$acc, ...$watchList->getDomains()->toArray()], []);

            /** @var Domain $domain */
            foreach ($watchList->getDomains()->getIterator() as $domain) {
                if (in_array($domain, $trackedDomains)) {
                    $ldhName = $domain->getLdhName();

                    $this->logger->notice('User {username} tried to create a watchlist with domain name {ldhName}. It is forbidden to register the same domain name twice with limited mode', [
                        'username' => $user->getUserIdentifier(),
                        'ldhName' => $ldhName,
                    ]);

                    throw new AccessDeniedHttpException("It is forbidden to register the same domain name twice in your watchlists with limited mode ($ldhName)");
                }
            }

            if (null !== $watchList->getWebhookDsn() && count($watchList->getWebhookDsn()) > (int)$this->getParameter('limit_max_watchlist_webhooks')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of webhooks has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of webhooks allowed in this Watchlist');
            }
        }

        $this->chatNotificationService->sendChatNotification($watchList, new TestChatNotification());
        $this->verifyConnector($watchList, $watchList->getConnector());

        $this->logger->info('User {username} registers a Watchlist ({token}).', [
            'username' => $user->getUserIdentifier(),
            'token' => $watchList->getToken(),
        ]);

        $this->em->persist($watchList);
        $this->em->flush();

        return $watchList;
    }

    #[Route(
        path: '/api/watchlists',
        name: 'watchlist_get_all_mine',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'get_all_mine',
        ],
        methods: ['GET']
    )]
    public function getWatchLists(): Collection
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user->getWatchLists();
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    private function verifyConnector(WatchList $watchList, ?Connector $connector): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null === $connector) {
            return;
        }
        if (!$user->getConnectors()->contains($connector)) {
            $this->logger->notice('The Connector ({connector}) does not belong to the user.', [
                'username' => $user->getUserIdentifier(),
                'connector' => $connector->getId(),
            ]);
            throw new AccessDeniedHttpException('You cannot create a Watchlist with a connector that does not belong to you');
        }

        /** @var Domain $domain */
        foreach ($watchList->getDomains()->getIterator() as $domain) {
            if ($domain->getDeleted()) {
                $ldhName = $domain->getLdhName();
                throw new BadRequestHttpException("To add a connector, no domain in this Watchlist must have already expired ($ldhName)");
            }
        }

        $connectorProviderClass = $connector->getProvider()->getConnectorProvider();
        /** @var AbstractProvider $connectorProvider */
        $connectorProvider = $this->locator->get($connectorProviderClass);

        $connectorProvider->authenticate($connector->getAuthData());
        $supported = $connectorProvider->isSupported(...$watchList->getDomains()->toArray());

        if (!$supported) {
            $this->logger->notice('The Connector ({connector}) does not support all TLDs in this Watchlist', [
                'username' => $user->getUserIdentifier(),
                'connector' => $connector->getId(),
            ]);
            throw new BadRequestHttpException('This connector does not support all TLDs in this Watchlist');
        }
    }

    /**
     * @throws ORMException
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws ExceptionInterface
     * @throws Exception
     */
    #[Route(
        path: '/api/watchlists/{token}',
        name: 'watchlist_update',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'update',
        ],
        methods: ['PUT']
    )]
    public function putWatchList(Request $request): WatchList
    {
        $watchList = $this->registerDomainsInWatchlist($request->getContent(), ['watchlist:create', 'watchlist:token']);

        /** @var User $user */
        $user = $this->getUser();
        $watchList->setUser($user);

        if ($this->getParameter('limited_features')) {
            if ($watchList->getDomains()->count() > (int)$this->getParameter('limit_max_watchlist_domains')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of domains has been reached for this Watchlist', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of domain names allowed in this Watchlist');
            }

            $userWatchLists = $user->getWatchLists();

            /** @var Domain[] $trackedDomains */
            $trackedDomains = $userWatchLists
                ->filter(fn(WatchList $wl) => $wl->getToken() !== $watchList->getToken())
                ->reduce(fn(array $acc, WatchList $wl) => [...$acc, ...$wl->getDomains()->toArray()], []);

            /** @var Domain $domain */
            foreach ($watchList->getDomains()->getIterator() as $domain) {
                if (in_array($domain, $trackedDomains)) {
                    $ldhName = $domain->getLdhName();
                    $this->logger->notice('User {username} tried to update a watchlist with domain name {ldhName}. It is forbidden to register the same domain name twice with limited mode', [
                        'username' => $user->getUserIdentifier(),
                        'ldhName' => $ldhName,
                    ]);

                    throw new AccessDeniedHttpException("It is forbidden to register the same domain name twice in your watchlists with limited mode ($ldhName)");
                }
            }

            if (null !== $watchList->getWebhookDsn() && count($watchList->getWebhookDsn()) > (int)$this->getParameter('limit_max_watchlist_webhooks')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of webhooks has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of webhooks allowed in this Watchlist');
            }
        }

        $this->chatNotificationService->sendChatNotification($watchList, new TestChatNotification());
        $this->verifyConnector($watchList, $watchList->getConnector());

        $this->logger->info('User {username} updates a Watchlist ({token}).', [
            'username' => $user->getUserIdentifier(),
            'token' => $watchList->getToken(),
        ]);

        $this->em->beginTransaction();

        /** @var WatchList $oldWatchlist */
        $oldWatchlist = $this->em->getReference(WatchList::class, $watchList->getToken());
        $this->em->lock($oldWatchlist, LockMode::PESSIMISTIC_WRITE);

        $this->em->remove($oldWatchlist);
        $this->em->flush();

        $this->em->persist($watchList);
        $this->em->flush();

        $this->em->commit();

        return $watchList;
    }

    /**
     * @throws ParseException
     * @throws EofException
     * @throws InvalidDataException
     * @throws Exception
     */
    #[Route(
        path: '/api/watchlists/{token}/calendar',
        name: 'watchlist_calendar',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'calendar',
        ]
    )]
    public function getWatchlistCalendar(string $token): Response
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $token]);

        $calendar = new Calendar();

        /** @var Domain $domain */
        foreach ($watchList->getDomains()->getIterator() as $domain) {
            foreach ($domain->getEvents() as $event) {
                $domain->addEvent($event);
            }
        }

        $calendarResponse = (new CalendarFactory())->createCalendar($calendar);
        $calendarName = $watchList->getName();
        if (null !== $calendarName) {
            $calendarResponse->withProperty(new Property('X-WR-CALNAME', new TextValue($calendarName)));
        }

        return new Response($calendarResponse, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: '/api/tracked',
        name: 'watchlist_get_tracked_domains',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'get_tracked_domains',
        ]
    )]
    public function getTrackedDomains(): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $domains = [];
        /** @var WatchList $watchList */
        foreach ($user->getWatchLists()->getIterator() as $watchList) {
            /** @var Domain $domain */
            foreach ($watchList->getDomains()->getIterator() as $domain) {
                /** @var DomainEvent|null $exp */
                $exp = $domain->getEvents()->findFirst(fn(int $key, DomainEvent $e) => !$e->getDeleted() && 'expiration' === $e->getAction());

                if (!$domain->getDeleted() && null !== $exp && !in_array($domain, $domains)) {
                    $domains[] = $domain;
                }
            }
        }

        usort($domains, fn(Domain $d1, Domain $d2) => $d1->getExpiresInDays() - $d2->getExpiresInDays());

        return $domains;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    private function registerDomainsInWatchlist(string $content, array $groups): WatchList
    {
        /** @var WatchList $watchList */
        $watchList = $this->serializer->deserialize($content, WatchList::class, 'json', ['groups' => $groups]);

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !isset($data['domains']) || !is_array($data['domains'])) {
            throw new BadRequestHttpException('Invalid payload: missing or invalid "domains" field.');
        }

        $domains = array_map(fn(string $d) => str_replace('/api/domains/', '', $d), $data['domains']);

        foreach ($domains as $ldhName) {
            /** @var ?Domain $domain */
            $domain = $this->domainRepository->findOneBy(['ldhName' => $ldhName]);

            if (null === $domain) {
                $domain = $this->RDAPService->registerDomain($ldhName);

                if (false === $this->kernel->isDebug() && true === $this->getParameter('limited_features')) {
                    $limiter = $this->rdapRequestsLimiter->create($this->getUser()->getUserIdentifier());
                    $limit = $limiter->consume();

                    if (!$limit->isAccepted()) {
                        throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
                    }
                }
            }

            $watchList->addDomain($domain);
        }

        return $watchList;
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: '/api/watchlists/{token}/rss/events',
        name: 'watchlist_rss_events',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'rss_events',
        ]
    )]
    public function getWatchlistRssEventsFeed(string $token, Request $request): Response
    {
        /** @var WatchList $watchlist */
        $watchlist = $this->watchListRepository->findOneBy(['token' => $token]);

        $feed = (new Feed())
            ->setLanguage('en')
            ->setCopyright('Domain Watchdog makes this information available "as is," and does not guarantee its accuracy.')
            ->setTitle('Domain events (' . $watchlist->getName().')')
            ->setGenerator("Domain Watchdog - RSS Feed", null, "https://github.com/maelgangloff/domain-watchdog")
            ->setDescription('The latest events for domain names in your Watchlist')
            ->setLink($request->getSchemeAndHttpHost() . "/#/tracking/watchlist")
            ->setFeedLink($request->getSchemeAndHttpHost() . "/api/watchlists/" . $token . '/rss/events', 'atom')
            ->setDateCreated($watchlist->getCreatedAt());

        /** @var Domain $domain */
        foreach ($watchlist->getDomains()->getIterator() as $domain) {
            foreach ($this->getRssEventEntries($request->getSchemeAndHttpHost(), $domain) as $entry) {
                $feed->addEntry($entry);
            }
        }

        return new Response($feed->export("atom"), Response::HTTP_OK, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: '/api/watchlists/{token}/rss/status',
        name: 'watchlist_rss_status',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'rss_status',
        ]
    )]
    public function getWatchlistRssStatusFeed(string $token, Request $request): Response
    {
        /** @var WatchList $watchlist */
        $watchlist = $this->watchListRepository->findOneBy(['token' => $token]);

        $feed = (new Feed())
            ->setLanguage('en')
            ->setCopyright('Domain Watchdog makes this information available "as is," and does not guarantee its accuracy.')
            ->setTitle('Domain EPP status (' . $watchlist->getName().')')
            ->setGenerator("Domain Watchdog - RSS Feed", null, "https://github.com/maelgangloff/domain-watchdog")
            ->setDescription('The latest changes to the EPP status of the domain names in your Watchlist')
            ->setLink($request->getSchemeAndHttpHost() . "/#/tracking/watchlist")
            ->setFeedLink($request->getSchemeAndHttpHost() . "/api/watchlists/" . $token . '/rss/status', 'atom')
            ->setDateCreated($watchlist->getCreatedAt());

        /** @var Domain $domain */
        foreach ($watchlist->getDomains()->getIterator() as $domain) {
            foreach ($this->getRssStatusEntries($request->getSchemeAndHttpHost(), $domain) as $entry) {
                $feed->addEntry($entry);
            }
        }

        return new Response($feed->export("atom"), Response::HTTP_OK, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }


    /**
     * @throws Exception
     */
    private function getRssEventEntries(string $baseUrl, Domain $domain): array
    {
        $entries = [];
        foreach ($domain->getEvents()->filter(fn (DomainEvent $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $event) {
            $entries[] = (new Entry())
                ->setId($baseUrl . "/api/domains/" . $domain->getLdhName() . '/events/' . $event->getId())
                ->setTitle($domain->getLdhName() . ': ' . $event->getAction(). '  - event update')
                ->setDescription("Domain name event")
                ->setLink($baseUrl . "/#/search/domain/" . $domain->getLdhName())
                ->setContent($this->render('rss/event_entry.html.twig', [
                    'event' => $event
                ])->getContent())
                ->setDateModified($event->getDate())
                ->addAuthor(["name" => strtoupper($domain->getTld()->getTld()) . " Registry"]);
        }

        return $entries;
    }

    /**
     * @throws Exception
     */
    private function getRssStatusEntries(string $baseUrl, Domain $domain): array
    {
        $entries = [];
        foreach ($domain->getDomainStatuses()->filter(fn (DomainStatus $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $domainStatus) {
            $authors = [["name" => strtoupper($domain->getTld()->getTld()) . " Registry"]];
            /** @var string $status */
            foreach ([...$domainStatus->getAddStatus(), ...$domainStatus->getDeleteStatus()] as $status) {
                if(str_starts_with($status, 'client')) {
                    $authors[] = ["name" => "Registrar"];
                    break;
                }
            }

            $entries[] = (new Entry())
                ->setId($baseUrl . "/api/domains/" . $domain->getLdhName() . '/status/' . $domainStatus->getId())
                ->setTitle($domain->getLdhName() . ' - EPP status update')
                ->setDescription("There has been a change in the EPP status of the domain name.")
                ->setLink($baseUrl . "/#/search/domain/" . $domain->getLdhName())
                ->setContent($this->render('rss/status_entry.html.twig', [
                    'domainStatus' => $domainStatus
                ])->getContent())
                ->setDateCreated($domainStatus->getCreatedAt())
                ->setDateModified($domainStatus->getDate())
                ->addCategory(["term" => $domain->getLdhName()])
                ->addCategory(["term" => strtoupper($domain->getTld()->getTld())])
                ->addAuthors($authors)
            ;
        }

        return $entries;
    }

}
