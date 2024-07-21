<?php

namespace App\MessageHandler;

use App\Config\TriggerAction;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\User;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\SendNotifWatchListTrigger;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Throwable;

#[AsMessageHandler]
readonly final class SendNotifWatchListTriggerHandler
{

    public function __construct(
        private WatchListRepository $watchListRepository,
        private RDAPService         $RDAPService,
        private MailerInterface     $mailer,
        private string              $mailerSenderEmail
    )
    {
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendNotifWatchListTrigger $message): void
    {
        /** @var WatchList $watchList */
        foreach ($this->watchListRepository->findAll() as $watchList) {

            /** @var Domain $domain */
            foreach ($watchList->getDomains()
                         ->filter(fn($domain) => $domain->getUpdatedAt()
                                 ->diff(new DateTimeImmutable('now'))->days >= 7) as $domain
            ) {
                $updatedAt = $domain->getUpdatedAt();

                try {
                    $domain = $this->RDAPService->registerDomain($domain->getLdhName());
                } catch (Throwable) {
                    $this->sendEmailDomainUpdateError($domain, $watchList->getUser());
                    continue;
                }

                /** @var DomainEvent $event */
                foreach ($domain->getEvents()->filter(fn($event) => $updatedAt < $event->getDate()) as $event) {

                    $watchListTriggers = $watchList->getWatchListTriggers()
                        ->filter(fn($trigger) => $trigger->getEvent() === $event->getAction());

                    /** @var WatchListTrigger $watchListTrigger */
                    foreach ($watchListTriggers->getIterator() as $watchListTrigger) {

                        switch ($watchListTrigger->getAction()) {
                            case TriggerAction::SendEmail:
                                $this->sendEmailDomainUpdated($event, $watchList->getUser());
                        }
                    }
                }
            }

        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailDomainUpdateError(Domain $domain, User $user): Email
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
        return $email;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailDomainUpdated(DomainEvent $domainEvent, User $user): Email
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerSenderEmail)
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGHEST)
            ->subject('A domain name has been changed')
            ->htmlTemplate('emails/domain_updated.html.twig')
            ->locale('en')
            ->context([
                "event" => $domainEvent
            ]);

        $this->mailer->send($email);
        return $email;
    }

}
