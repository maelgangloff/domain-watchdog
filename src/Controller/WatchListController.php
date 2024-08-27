<?php

namespace App\Controller;

use App\Config\Provider\AbstractProvider;
use App\Config\WebhookScheme;
use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Notifier\TestChatNotification;
use App\Repository\WatchListRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Value\TextValue;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WatchListController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $em,
        private readonly WatchListRepository $watchListRepository,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly KernelInterface $kernel
    ) {
    }

    /**
     * @throws \Exception
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
        $watchList = $this->serializer->deserialize($request->getContent(), WatchList::class, 'json', ['groups' => 'watchlist:create']);

        /** @var User $user */
        $user = $this->getUser();
        $watchList->setUser($user);

        /*
         * In the limited version, we do not want a user to be able to register the same domain more than once in their watchlists.
         * This policy guarantees the equal probability of obtaining a domain name if it is requested by several users.
         */
        if ($this->getParameter('limited_features')) {
            if ($watchList->getDomains()->count() > (int) $this->getParameter('limit_max_watchlist_domains')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of domains has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of domain names allowed in this Watchlist');
            }

            $userWatchLists = $user->getWatchLists();
            if ($userWatchLists->count() >= (int) $this->getParameter('limit_max_watchlist')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of Watchlists has been reached', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of Watchlists allowed');
            }

            /** @var Domain[] $trackedDomains */
            $trackedDomains = $userWatchLists->reduce(fn (array $acc, WatchList $watchList) => [...$acc, ...$watchList->getDomains()->toArray()], []);

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

            if (null !== $watchList->getWebhookDsn() && count($watchList->getWebhookDsn()) > (int) $this->getParameter('limit_max_watchlist_webhooks')) {
                $this->logger->notice('User {username} tried to create a Watchlist. The maximum number of webhooks has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of webhooks allowed in this Watchlist');
            }
        }

        $this->verifyWebhookDSN($watchList);
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

    private function verifyWebhookDSN(WatchList $watchList): void
    {
        $webhookDsn = $watchList->getWebhookDsn();
        if (null !== $webhookDsn && 0 !== count($webhookDsn)) {
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
                } catch (\Throwable $exception) {
                    throw new BadRequestHttpException($exception->getMessage());
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function verifyConnector(WatchList $watchList, ?Connector $connector): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null !== $connector) {
            if (!$user->getConnectors()->contains($connector)) {
                $this->logger->notice('The Connector ({connector}) does not belong to the user.', [
                    'username' => $user->getUserIdentifier(),
                    'connector' => $connector->getId(),
                ]);
                throw new AccessDeniedHttpException('You cannot create a Watchlist with a connector that does not belong to you');
            }

            $connectorProviderClass = $connector->getProvider()->getConnectorProvider();
            /** @var AbstractProvider $connectorProvider */
            $connectorProvider = new $connectorProviderClass($connector->getAuthData(), $this->httpClient, $this->cacheItemPool, $this->kernel);

            $connectorProvider::verifyAuthData($connector->getAuthData(), $this->httpClient); // We want to check if the tokens are OK
            $supported = $connectorProvider->isSupported(...$watchList->getDomains()->toArray());

            if (!$supported) {
                $this->logger->notice('The Connector ({connector}) does not support all TLDs in this Watchlist', [
                    'username' => $user->getUserIdentifier(),
                    'connector' => $connector->getId(),
                ]);
                throw new BadRequestHttpException('This connector does not support all TLDs in this Watchlist');
            }
        }
    }

    /**
     * @throws ORMException
     * @throws \Exception
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
    public function putWatchList(WatchList $watchList): WatchList
    {
        /** @var User $user */
        $user = $this->getUser();
        $watchList->setUser($user);

        if ($this->getParameter('limited_features')) {
            if ($watchList->getDomains()->count() > (int) $this->getParameter('limit_max_watchlist_domains')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of domains has been reached for this Watchlist', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of domain names allowed in this Watchlist');
            }

            $userWatchLists = $user->getWatchLists();

            /** @var Domain[] $trackedDomains */
            $trackedDomains = $userWatchLists
                ->filter(fn (WatchList $wl) => $wl->getToken() !== $watchList->getToken())
                ->reduce(fn (array $acc, WatchList $wl) => [...$acc, ...$wl->getDomains()->toArray()], []);

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

            if (null !== $watchList->getWebhookDsn() && count($watchList->getWebhookDsn()) > (int) $this->getParameter('limit_max_watchlist_webhooks')) {
                $this->logger->notice('User {username} tried to update a Watchlist. The maximum number of webhooks has been reached.', [
                    'username' => $user->getUserIdentifier(),
                ]);
                throw new AccessDeniedHttpException('You have exceeded the maximum number of webhooks allowed in this Watchlist');
            }
        }

        $this->verifyWebhookDSN($watchList);
        $this->verifyConnector($watchList, $watchList->getConnector());

        $this->logger->info('User {username} updates a Watchlist ({token}).', [
            'username' => $user->getUserIdentifier(),
            'token' => $watchList->getToken(),
        ]);

        $this->em->remove($this->em->getReference(WatchList::class, $watchList->getToken()));
        $this->em->flush();

        $this->em->persist($watchList);
        $this->em->flush();

        return $watchList;
    }

    /**
     * @throws ParseException
     * @throws EofException
     * @throws InvalidDataException
     * @throws \Exception
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
            $attendees = [];

            /** @var DomainEntity $entity */
            foreach ($domain->getDomainEntities()->toArray() as $entity) {
                $vCard = Reader::readJson($entity->getEntity()->getJCard());
                if (isset($vCard->EMAIL) && isset($vCard->FN)) {
                    $email = (string) $vCard->EMAIL;
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }
                    $attendees[] = (new Attendee(new EmailAddress($email)))->setDisplayName((string) $vCard->FN);
                }
            }

            /** @var DomainEvent $event */
            foreach ($domain->getEvents()->toArray() as $event) {
                $calendar->addEvent((new Event())
                    ->setLastModified(new Timestamp($domain->getUpdatedAt()))
                    ->setStatus(EventStatus::CONFIRMED())
                    ->setSummary($domain->getLdhName().' ('.$event->getAction().')')
                    ->addCategory(new Category($event->getAction()))
                    ->setAttendees($attendees)
                    ->setOccurrence(new SingleDay(new Date($event->getDate())))
                );
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
}
