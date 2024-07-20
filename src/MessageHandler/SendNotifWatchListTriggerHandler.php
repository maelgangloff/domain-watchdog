<?php

namespace App\MessageHandler;

use App\Config\TriggerAction;
use App\Entity\Domain;
use App\Entity\DomainEvent;
use App\Entity\WatchList;
use App\Entity\WatchListTrigger;
use App\Message\SendNotifWatchListTrigger;
use App\Repository\WatchListRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final class SendNotifWatchListTriggerHandler
{

    public function __construct(
        private WatchListRepository $watchListRepository,
        private RDAPService         $RDAPService
    )
    {
    }

    /**
     * @throws Exception
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

                }
                /** @var DomainEvent $event */
                foreach ($domain->getEvents()->filter(fn($event) => $updatedAt < $event->getDate()) as $event) {

                    $watchListTriggers = $watchList->getWatchListTriggers()
                        ->filter(fn($trigger) => $trigger->getAction() === $event->getAction());

                    /** @var WatchListTrigger $watchListTrigger */
                    foreach ($watchListTriggers->getIterator() as $watchListTrigger) {

                        switch ($watchListTrigger->getAction()) {
                            case TriggerAction::SendEmail:
                                //TODO: To be implemented
                                throw new Exception('To be implemented');
                        }
                    }
                }
            }

        }
    }
}
