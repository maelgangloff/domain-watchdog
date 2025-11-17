<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\RdapServer;
use App\Entity\Watchlist;
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
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;
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
        private LoggerInterface $logger,
        private LockFactory $lockFactory,
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
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function __invoke(UpdateDomain $message): void
    {
        /** @var ?Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $message->ldhName]);

        if (null !== $domain && $message->onlyNew) {
            $this->logger->debug('The domain name is already present in the database', [
                'ldhName' => $domain->getLdhName(),
            ]);

            return;
        }

        if (null === $domain) {
            $lock = $this->createDomainLock($message->ldhName);

            if (!$lock->acquire()) {
                $this->logger->notice('Update of this domain name is locked because it is already in progress', [
                    'ldhName' => $message->ldhName,
                ]);

                return;
            }

            try {
                $this->RDAPService->registerDomain($message->ldhName);
            } catch (TldNotSupportedException|MalformedDomainException $exception) {
                throw new UnrecoverableMessageHandlingException($exception->getMessage(), 0, $exception);
            } catch (UnknownRdapServerException|DomainNotFoundException) {
                return;
            } finally {
                $lock->release();
            }

            return;
        }

        /** @var ?RdapServer $rdapServer */
        $rdapServer = $domain->getTld()->getRdapServers()->first();
        if (null === $rdapServer) {
            $this->logger->warning('No RDAP server for this domain name', [
                'ldhName' => $domain->getLdhName(),
            ]);

            return;
        }

        $watchlist = $this->watchlistRepository->findOneBy(['token' => $message->watchlistToken]);

        if (null === $watchlist) {
            /** @var Watchlist $wl */
            foreach ($domain->getWatchlists()->getIterator() as $wl) {
                $this->bus->dispatch(new UpdateDomain($message->ldhName, $wl->getToken(), false), [
                    new TransportNamesStamp('rdap_low'),
                ]);
            }

            return;
        }

        if (!$this->RDAPService->isToBeUpdated($domain, false, null !== $watchlist->getConnector())) {
            $this->logger->debug('The domain name is already present in the database and does not need to be updated at this time', [
                'ldhName' => $domain->getLdhName(),
            ]);

            return;
        }

        $updatedAt = $domain->getUpdatedAt();
        $deleted = $domain->getDeleted();

        $lock = $this->createDomainLock($message->ldhName);

        if (!$lock->acquire()) {
            $this->logger->notice('Update of this domain name is locked because it is already in progress', [
                'ldhName' => $message->ldhName,
            ]);

            return;
        }

        try {
            /*
             * Domain name update
             * We send messages that correspond to the sending of notifications that will not be processed here.
             */
            $this->RDAPService->registerDomain($message->ldhName);

            /** @var Watchlist $wl */
            foreach ($domain->getWatchlists()->getIterator() as $wl) {
                $this->bus->dispatch(new DetectDomainChange($wl->getToken(), $domain->getLdhName(), $updatedAt));
            }
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
                $this->bus->dispatch(new OrderDomain($watchlist->getToken(), $domain->getLdhName(), $updatedAt));
            }
        } catch (TldNotSupportedException|MalformedDomainException $exception) {
            /*
             * In this case, the domain name can no longer be updated. Unfortunately, there is nothing more that can be done.
             */
            throw new UnrecoverableMessageHandlingException($exception->getMessage(), 0, $exception);
        } catch (UnknownRdapServerException) {
            return;
        } finally {
            $lock->release();
        }
    }

    private function createDomainLock(string $ldhName): SharedLockInterface
    {
        return $this->lockFactory->createLockFromKey(
            new Key('domain_update.'.$ldhName),
            ttl: 600,
            autoRelease: false
        );
    }
}
