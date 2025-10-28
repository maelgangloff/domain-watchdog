<?php

namespace App\MessageHandler;

use App\Entity\RdapServer;
use App\Exception\DomainNotFoundException;
use App\Exception\MalformedDomainException;
use App\Exception\TldNotSupportedException;
use App\Exception\UnknownRdapServerException;
use App\Exception\UnsupportedDsnSchemeException;
use App\Message\DetectDomainChange;
use App\Message\OrderDomain;
use App\Message\UpdateDomain;
use App\Notifier\DomainDeletedNotification;
use App\Repository\DomainRepository;
use App\Repository\WatchlistRepository;
use App\Service\ChatNotificationService;
use App\Service\RDAPService;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[AsMessageHandler]
final readonly class UpdateDomainHandler
{
    private Address $sender;

    public function __construct(
        private RDAPService $RDAPService,
        private ChatNotificationService $chatNotificationService,
        private MailerInterface $mailer,
        string $mailerSenderEmail,
        string $mailerSenderName,
        private MessageBusInterface $bus,
        private WatchlistRepository $watchlistRepository,
        private DomainRepository $domainRepository,
        private RateLimiterFactory $rdapRequestsLimiter, private LoggerInterface $logger,
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Symfony\Component\Notifier\Exception\TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws OptimisticLockException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws UnsupportedDsnSchemeException
     * @throws ServerExceptionInterface
     * @throws MalformedDomainException
     * @throws ExceptionInterface
     */
    public function __invoke(UpdateDomain $message): void
    {
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);
        /** @var ?RdapServer $rdapServer */
        $rdapServer = $domain->getTld()->getRdapServers()->first();
        if (null === $rdapServer) {
            $this->logger->warning('No RDAP server for this domain name', [
                'ldhName' => $domain->getLdhName(),
            ]);

            return;
        }
        $limiter = $this->rdapRequestsLimiter->create($rdapServer->getUrl());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

            $this->logger->warning('Security rate limit reached for this RDAP server', [
                'url' => $rdapServer->getUrl(),
                'retryAfter' => $retryAfter,
            ]);

            throw new RecoverableMessageHandlingException('Rate limit reached', 0, null, $retryAfter);
        }

        $watchlist = $this->watchlistRepository->findOneBy(['token' => $message->watchlistToken]);

        $updatedAt = $domain->getUpdatedAt();
        $deleted = $domain->getDeleted();

        try {
            /*
             * Domain name update
             * We send messages that correspond to the sending of notifications that will not be processed here.
             */
            $this->RDAPService->registerDomain($domain->getLdhName());
            $this->bus->dispatch(new DetectDomainChange($watchlist->getToken(), $domain->getLdhName(), $updatedAt));
        } catch (DomainNotFoundException) {
            $newDomain = $this->domainRepository->findOneBy(['ldhName' => $domain->getLdhName()]);

            if (!$deleted && null !== $newDomain && $newDomain->getDeleted()) {
                $notification = new DomainDeletedNotification($this->sender, $domain);
                $this->mailer->send($notification->asEmailMessage(new Recipient($watchlist->getUser()->getEmail()))->getMessage());
                $this->chatNotificationService->sendChatNotification($watchlist, $notification);
            }

            if ($watchlist->getConnector()) {
                /*
                 * If the domain name no longer appears in the WHOIS AND a connector is associated with this Watchlist,
                 * this connector is used to purchase the domain name.
                 */
                $this->bus->dispatch(new OrderDomain($watchlist->getToken(), $domain->getLdhName()));
            }
        } catch (TldNotSupportedException|UnknownRdapServerException) {
            /*
             * In this case, the domain name can no longer be updated. Unfortunately, there is nothing more that can be done.
             */
        }
    }
}
