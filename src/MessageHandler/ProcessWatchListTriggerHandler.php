<?php

namespace App\MessageHandler;

use App\Entity\Domain;
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
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Throwable;

#[AsMessageHandler]
final readonly class ProcessWatchListTriggerHandler
{

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
                         || $this->RDAPService::isToBeWatchClosely($domain, $domain->getUpdatedAt())
                     ) as $domain
        ) {
            $updatedAt = $domain->getUpdatedAt();

            try {
                $domain = $this->RDAPService->registerDomain($domain->getLdhName());
            } catch (HttpExceptionInterface) { // To allow the purchase of expired domains
                $this->sendEmailDomainUpdateError($domain, $watchList->getUser());
            } catch (Throwable) {
                $this->sendEmailDomainUpdateError($domain, $watchList->getUser());
                continue;
            }

            /*
             * This code is deliberately commented to avoid unwanted side effects.
             * Indeed, although it is useful and relevant to schedule a new update every day,
             * there is a risk that the messages will get carried away by chain reaction.
             * In theory, a new message chain would be generated every week.
             *
             * if ($this->RDAPService::isToBeWatchClosely($domain, $updatedAt)) {
             *     $this->bus->dispatch(new ProcessWatchListTrigger($message->watchListToken), [
             *         new DelayStamp(24 * 60 * 60 * 1e3)
             *     ]);
             * }
            */

            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }
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
