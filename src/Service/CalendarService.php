<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Sabre\VObject\EofException;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

readonly class CalendarService
{
    public function __construct(
        private RDAPService $RDAPService,
    ) {
    }

    /**
     * @return Event[]
     *
     * @throws ParseException
     * @throws EofException
     * @throws InvalidDataException
     * @throws \Exception
     */
    public function getDomainCalendarEvents(Domain $domain): array
    {
        $events = [];
        $attendees = [];

        /* @var DomainEntity $entity */
        foreach ($domain->getDomainEntities()->filter(fn (DomainEntity $domainEntity) => !$domainEntity->getDeletedAt())->getIterator() as $domainEntity) {
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
            $events[] = (new Event())
                ->setLastModified(new Timestamp($domain->getUpdatedAt()))
                ->setStatus(EventStatus::CONFIRMED())
                ->setSummary($domain->getLdhName().': '.$event->getAction())
                ->addCategory(new Category($event->getAction()))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date($event->getDate()))
                );
        }

        $expiresInDays = $this->RDAPService->getExpiresInDays($domain);

        if (null !== $expiresInDays) {
            $events[] = (new Event())
                ->setLastModified(new Timestamp($domain->getUpdatedAt()))
                ->setStatus(EventStatus::CONFIRMED())
                ->setSummary($domain->getLdhName().': estimated WHOIS release date')
                ->addCategory(new Category('release'))
                ->setAttendees($attendees)
                ->setOccurrence(new SingleDay(new Date(
                    (new \DateTimeImmutable())->setTime(0, 0)->add(new \DateInterval('P'.$expiresInDays.'D'))
                ))
                );
        }

        return $events;
    }
}
