<?php

namespace App\Controller;

use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Repository\WatchListRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Exception;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class WatchListController extends AbstractController
{

    public function __construct(
        private readonly SerializerInterface    $serializer,
        private readonly EntityManagerInterface $em,
        private readonly WatchListRepository    $watchListRepository
    )
    {
    }

    /**
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
        $watchList = $this->serializer->deserialize($request->getContent(), WatchList::class, 'json', ['groups' => 'watchlist:create']);
        $watchList->setUser($this->getUser());

        $this->em->persist($watchList);
        $this->em->flush();

        return $watchList;
    }

    /**
     * @throws ParseException
     * @throws EofException
     * @throws InvalidDataException
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
        $watchList = $this->watchListRepository->findOneBy(["token" => $token]);
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getWatchLists()->contains($watchList)) throw new UnauthorizedHttpException('');

        $calendar = new Calendar();

        foreach ($watchList->getDomains()->getIterator() as $domain) {
            $attendees = [];

            /** @var DomainEntity $entity */
            foreach ($domain->getDomainEntities()->toArray() as $entity) {
                $vCard = Reader::readJson($entity->getEntity()->getJCard());
                $email = (string)$vCard->EMAIL;
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                $attendees[] = (new Attendee(new EmailAddress($email)))->setDisplayName((string)$vCard->FN);
            }

            /** @var DomainEvent $event */
            foreach ($domain->getEvents()->toArray() as $event) {
                $calendar->addEvent((new Event())
                    ->setSummary($domain->getLdhName() . ' (' . $event->getAction() . ')')
                    ->addCategory(new Category($event->getAction()))
                    ->setAttendees($attendees)
                    ->setOccurrence(new SingleDay(new Date($event->getDate())))
                );
            }
        }

        return new Response((new CalendarFactory())->createCalendar($calendar), Response::HTTP_OK, [
            "Content-Type" => 'text/calendar; charset=utf-8'
        ]);
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

}