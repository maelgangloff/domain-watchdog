<?php

namespace App\Controller;

use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use App\Repository\DomainRepository;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Exception;
use Sabre\VObject\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DomainCalendarController extends AbstractController
{
    public function __construct(
        private readonly DomainRepository $domainRepository
    )
    {

    }

    /**
     * @throws Exception
     */
    public function __invoke(string $ldhName): Response
    {
        $calendar = new Calendar();

        $domain = $this->domainRepository->findOneBy(["ldhName" => $ldhName]);
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
                ->setSummary($domain->getLdhName() . ' (' . $event->getAction()->value . ')')
                ->addCategory(new Category($event->getAction()->value))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date($event->getDate())))
            );
        }
        return new Response((new CalendarFactory())->createCalendar($calendar), Response::HTTP_OK, [
            "Content-Type" => 'text/calendar; charset=utf-8'
        ]);
    }
}