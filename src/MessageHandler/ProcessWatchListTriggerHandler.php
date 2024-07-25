<?php

namespace App\MessageHandler;

use App\Config\EventAction;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Message\ProcessDomainTrigger;
use App\Message\ProcessWatchListTrigger;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;

#[AsMessageHandler]
final readonly class ProcessWatchListTriggerHandler
{
    const IMPORTANT_EVENTS = [EventAction::Deletion->value, EventAction::Expiration->value];

    public function __construct(
        private RDAPService         $RDAPService,
        private MailerInterface     $mailer,
        private string              $mailerSenderEmail,
        private MessageBusInterface $bus,
        private WatchListRepository $watchListRepository
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessWatchListTrigger $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(["token" => $message->watchListToken]);
        /** @var Domain $domain */
        foreach ($watchList->getDomains()
                     ->filter(fn($domain) => $domain->getUpdatedAt()
                             ->diff(
                                 new DateTimeImmutable('now'))->days >= 7
                         || self::isToBeWatchClosely($domain, $domain->getUpdatedAt())
                     ) as $domain
        ) {
            $updatedAt = $domain->getUpdatedAt();

            try {
                $domain = $this->RDAPService->registerDomain($domain->getLdhName());
            } catch (Throwable) {
                $this->sendEmailDomainUpdateError($domain, $watchList->getUser());
                continue;
            }

            /**
             * If the domain name must be consulted regularly, we reschedule an update in one day
             */
            if (self::isToBeWatchClosely($domain, $updatedAt)) {
                $this->bus->dispatch(new ProcessWatchListTrigger($message->watchListToken), [
                    new DelayStamp(24 * 60 * 60 * 1e3)]);
            }

            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }
    }

    /**
     * Determines if a domain name needs special attention.
     * These domain names are those whose last event was expiration or deletion.
     * @throws Exception
     */
    public static function isToBeWatchClosely(Domain $domain, DateTimeImmutable $updatedAt): bool
    {
        if ($updatedAt->diff(new DateTimeImmutable('now'))->days < 1) return false;

        /** @var DomainEvent[] $events */
        $events = $domain->getEvents()->toArray();

        usort($events, fn(DomainEvent $e1, DomainEvent $e2) => $e2->getDate() - $e1->getDate());

        return !empty($events) && in_array($events[0]->getAction(), self::IMPORTANT_EVENTS);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmailDomainUpdateError(Domain $domain, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerSenderEmail)
            ->to($user->getEmail())
            ->subject('An error occurred while updating a domain name')
            ->htmlTemplate('emails/errors/domain_update.html.twig')
            ->locale('en')
            ->context([
                "domain" => $domain
            ]);

        $this->mailer->send($email);
    }
}
