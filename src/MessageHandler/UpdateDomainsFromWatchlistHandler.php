<?php

namespace App\MessageHandler;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\OrderDomain;
use App\Message\SendDomainEventNotif;
use App\Message\UpdateDomainsFromWatchlist;
use App\Notifier\DomainDeletedNotification;
use App\Notifier\DomainUpdateErrorNotification;
use App\Repository\WatchListRepository;
use App\Service\ChatNotificationService;
use App\Service\Connector\AbstractProvider;
use App\Service\Connector\CheckDomainProviderInterface;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
final readonly class UpdateDomainsFromWatchlistHandler
{
    private Address $sender;

    public function __construct(
        private RDAPService $RDAPService,
        private ChatNotificationService $chatNotificationService,
        private MailerInterface $mailer,
        string $mailerSenderEmail,
        string $mailerSenderName,
        private MessageBusInterface $bus,
        private WatchListRepository $watchListRepository,
        private LoggerInterface $logger,
        #[Autowire(service: 'service_container')]
        private ContainerInterface $locator,
    ) {
        $this->sender = new Address($mailerSenderEmail, $mailerSenderName);
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(UpdateDomainsFromWatchlist $message): void
    {
        /** @var WatchList $watchList */
        $watchList = $this->watchListRepository->findOneBy(['token' => $message->watchListToken]);

        $this->logger->info('Domain names from Watchlist {token} will be processed.', [
            'token' => $message->watchListToken,
        ]);

        /** @var AbstractProvider $connectorProvider */
        $connectorProvider = $this->getConnectorProvider($watchList);

        if ($connectorProvider instanceof CheckDomainProviderInterface) {
            $this->logger->notice('Watchlist {watchlist} linked to connector {connector}.', [
                'watchlist' => $watchList->getToken(),
                'connector' => $watchList->getConnector()->getId(),
            ]);

            try {
                $checkedDomains = $connectorProvider->checkDomains(
                    ...array_unique(array_map(fn (Domain $d) => $d->getLdhName(), $watchList->getDomains()->toArray()))
                );
            } catch (\Throwable $exception) {
                $this->logger->warning('Unable to check domain names availability with connector {connector}.', [
                    'connector' => $watchList->getConnector()->getId(),
                ]);

                throw $exception;
            }

            foreach ($checkedDomains as $domain) {
                $this->bus->dispatch(new OrderDomain($watchList->getToken(), $domain));
            }

            return;
        }

        /*
         * A domain name is updated if one or more of these conditions are met:
         * - was updated more than 7 days ago
         * - has statuses that suggest it will expire soon AND was updated more than 15 minutes ago
         * - has specific statuses that suggest there is a dispute between the registrant and the registrar AND was updated more than a day ago
         */

        /** @var Domain $domain */
        foreach ($watchList->getDomains()->filter(fn ($domain) => $domain->isToBeUpdated(false, null !== $watchList->getConnector())) as $domain
        ) {
            $updatedAt = $domain->getUpdatedAt();

            try {
                /*
                 * Domain name update
                 * We send messages that correspond to the sending of notifications that will not be processed here.
                 */
                $this->RDAPService->registerDomain($domain->getLdhName());
                $this->bus->dispatch(new SendDomainEventNotif($watchList->getToken(), $domain->getLdhName(), $updatedAt));
            } catch (NotFoundHttpException) {
                if (!$domain->getDeleted()) {
                    $notification = new DomainDeletedNotification($this->sender, $domain);
                    $this->mailer->send($notification->asEmailMessage(new Recipient($watchList->getUser()->getEmail()))->getMessage());
                    $this->chatNotificationService->sendChatNotification($watchList, $notification);
                }

                if ($watchList->getConnector()) {
                    /*
                     * If the domain name no longer appears in the WHOIS AND a connector is associated with this Watchlist,
                     * this connector is used to purchase the domain name.
                     */
                    $this->bus->dispatch(new OrderDomain($watchList->getToken(), $domain->getLdhName()));
                }
            } catch (\Throwable $e) {
                /*
                 * In case of another unknown error,
                 * the owner of the Watchlist is informed that an error occurred in updating the domain name.
                 */
                $this->logger->error('An update error email is sent to user {username}.', [
                    'username' => $watchList->getUser()->getUserIdentifier(),
                    'error' => $e,
                ]);
                $email = (new DomainUpdateErrorNotification($this->sender, $domain))
                    ->asEmailMessage(new Recipient($watchList->getUser()->getEmail()));
                $this->mailer->send($email->getMessage());

                throw $e;
            }
        }
    }

    private function getConnectorProvider(WatchList $watchList): ?object
    {
        $connector = $watchList->getConnector();
        if (null === $connector || null === $connector->getProvider()) {
            return null;
        }

        $providerClass = $connector->getProvider()->getConnectorProvider();

        return $this->locator->get($providerClass);
    }
}
