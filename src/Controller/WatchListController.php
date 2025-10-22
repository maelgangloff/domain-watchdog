<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\User;
use App\Entity\WatchList;
use App\Repository\DomainEventRepository;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use Doctrine\Common\Collections\Collection;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Value\TextValue;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Laminas\Feed\Writer\Entry;
use Laminas\Feed\Writer\Feed;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WatchListController extends AbstractController
{
    public function __construct(
        private readonly WatchListRepository $watchListRepository,
        private readonly DomainEventRepository $domainEventRepository,
        private readonly RDAPService $RDAPService,
    ) {
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
            foreach ($domain->getDomainCalendarEvents() as $event) {
                $calendar->addEvent($event);
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
     * @throws \Exception
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
                $exp = $this->domainEventRepository->findLastDomainEvent($domain, 'expiration');

                if (!$domain->getDeleted() && null !== $exp && !in_array($domain, $domains)) {
                    $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
                    $domains[] = $domain;
                }
            }
        }

        usort($domains, fn (Domain $d1, Domain $d2) => $d1->getExpiresInDays() - $d2->getExpiresInDays());

        return $domains;
    }

    /**
     * @throws \Exception
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
            ->setTitle('Domain events ('.$watchlist->getName().')')
            ->setGenerator('Domain Watchdog - RSS Feed', null, 'https://github.com/maelgangloff/domain-watchdog')
            ->setDescription('The latest events for domain names in your Watchlist')
            ->setLink($request->getSchemeAndHttpHost().'/#/tracking/watchlist')
            ->setFeedLink($request->getSchemeAndHttpHost().'/api/watchlists/'.$token.'/rss/events', 'atom')
            ->setDateCreated($watchlist->getCreatedAt());

        /** @var Domain $domain */
        foreach ($watchlist->getDomains()->getIterator() as $domain) {
            foreach ($this->getRssEventEntries($request->getSchemeAndHttpHost(), $domain) as $entry) {
                $feed->addEntry($entry);
            }
        }

        return new Response($feed->export('atom'), Response::HTTP_OK, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }

    /**
     * @throws \Exception
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
            ->setTitle('Domain EPP status ('.$watchlist->getName().')')
            ->setGenerator('Domain Watchdog - RSS Feed', null, 'https://github.com/maelgangloff/domain-watchdog')
            ->setDescription('The latest changes to the EPP status of the domain names in your Watchlist')
            ->setLink($request->getSchemeAndHttpHost().'/#/tracking/watchlist')
            ->setFeedLink($request->getSchemeAndHttpHost().'/api/watchlists/'.$token.'/rss/status', 'atom')
            ->setDateCreated($watchlist->getCreatedAt());

        /** @var Domain $domain */
        foreach ($watchlist->getDomains()->getIterator() as $domain) {
            foreach ($this->getRssStatusEntries($request->getSchemeAndHttpHost(), $domain) as $entry) {
                $feed->addEntry($entry);
            }
        }

        return new Response($feed->export('atom'), Response::HTTP_OK, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }

    /**
     * @throws \Exception
     */
    private function getRssEventEntries(string $baseUrl, Domain $domain): array
    {
        $entries = [];
        foreach ($domain->getEvents()->filter(fn (DomainEvent $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $event) {
            $entries[] = (new Entry())
                ->setId($baseUrl.'/api/domains/'.$domain->getLdhName().'#events-'.$event->getId())
                ->setTitle($domain->getLdhName().': '.$event->getAction().'  - event update')
                ->setDescription('Domain name event')
                ->setLink($baseUrl.'/#/search/domain/'.$domain->getLdhName())
                ->setContent($this->render('rss/event_entry.html.twig', [
                    'event' => $event,
                ])->getContent())
                ->setDateModified($event->getDate())
                ->addAuthor(['name' => strtoupper($domain->getTld()->getTld()).' Registry']);
        }

        return $entries;
    }

    /**
     * @throws \Exception
     */
    private function getRssStatusEntries(string $baseUrl, Domain $domain): array
    {
        $entries = [];
        foreach ($domain->getDomainStatuses()->filter(fn (DomainStatus $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $domainStatus) {
            $authors = [['name' => strtoupper($domain->getTld()->getTld()).' Registry']];
            /** @var string $status */
            foreach ([...$domainStatus->getAddStatus(), ...$domainStatus->getDeleteStatus()] as $status) {
                if (str_starts_with($status, 'client')) {
                    $authors[] = ['name' => 'Registrar'];
                    break;
                }
            }

            $entries[] = (new Entry())
                ->setId($baseUrl.'/api/domains/'.$domain->getLdhName().'#status-'.$domainStatus->getId())
                ->setTitle($domain->getLdhName().' - EPP status update')
                ->setDescription('There has been a change in the EPP status of the domain name.')
                ->setLink($baseUrl.'/#/search/domain/'.$domain->getLdhName())
                ->setContent($this->render('rss/status_entry.html.twig', [
                    'domainStatus' => $domainStatus,
                ])->getContent())
                ->setDateCreated($domainStatus->getCreatedAt())
                ->setDateModified($domainStatus->getDate())
                ->addCategory(['term' => $domain->getLdhName()])
                ->addCategory(['term' => strtoupper($domain->getTld()->getTld())])
                ->addAuthors($authors)
            ;
        }

        return $entries;
    }
}
