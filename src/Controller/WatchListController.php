<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
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
use Psr\Log\LoggerInterface;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
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
        private readonly WatchListRepository $watchListRepository,
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
            $attendees = [];

            /* @var DomainEntity $entity */
            foreach ($domain->getDomainEntities()->filter(fn (DomainEntity $domainEntity) => !$domainEntity->getDeleted())->getIterator() as $domainEntity) {
                $jCard = $domainEntity->getEntity()->getJCard();

                if (empty($jCard)) {
                    continue;
                }

                $vCardData = Reader::readJson($jCard);

                if (empty($vCardData->EMAIL) || empty($vCardData->FN)) {
                    continue;
                }

                $email = (string) $vCardData->EMAIL;

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $attendees[] = (new Attendee(new EmailAddress($email)))->setDisplayName((string) $vCardData->FN);
            }

            /** @var DomainEvent $event */
            foreach ($domain->getEvents()->filter(fn (DomainEvent $e) => $e->getDate()->diff(new \DateTimeImmutable('now'))->y <= 10)->getIterator() as $event) {
                $calendar->addEvent((new Event())
                    ->setLastModified(new Timestamp($domain->getUpdatedAt()))
                    ->setStatus(EventStatus::CONFIRMED())
                    ->setSummary($domain->getLdhName().': '.$event->getAction())
                    ->addCategory(new Category($event->getAction()))
                    ->setAttendees($attendees)
                    ->setOccurrence(new SingleDay(new Date($event->getDate())))
                );
            }

            $expiresInDays = $domain->getExpiresInDays();

            if (null === $expiresInDays) {
                continue;
            }

            $calendar->addEvent((new Event())
                ->setLastModified(new Timestamp($domain->getUpdatedAt()))
                ->setStatus(EventStatus::CONFIRMED())
                ->setSummary($domain->getLdhName().': estimated WHOIS release date')
                ->addCategory(new Category('release'))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date(
                    (new \DateTimeImmutable())->setTime(0, 0)->add(new \DateInterval('P'.$expiresInDays.'D'))
                )))
            );
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
                $exp = $domain->getEvents()->findFirst(fn (int $key, DomainEvent $e) => !$e->getDeleted() && 'expiration' === $e->getAction());

                if (!$domain->getDeleted() && null !== $exp && !in_array($domain, $domains)) {
                    $domains[] = $domain;
                }
            }
        }

        usort($domains, fn (Domain $d1, Domain $d2) => $d1->getExpiresInDays() - $d2->getExpiresInDays());

        return $domains;
    }
}
