<?php

namespace App\MessageHandler;

use App\Entity\WatchList;
use App\Message\ProcessWatchListsTrigger;
use App\Message\ProcessWatchListTrigger;
use App\Repository\WatchListRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessWatchListsTriggerHandler
{
    public function __construct(
        private WatchListRepository $watchListRepository,
        private MessageBusInterface $bus
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessWatchListsTrigger $message): void
    {
        /** @var WatchList $watchList */
        foreach ($this->watchListRepository->findAll() as $watchList) {
            $this->bus->dispatch(new ProcessWatchListTrigger($watchList->getToken()));
        }
    }
}
