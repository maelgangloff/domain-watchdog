<?php

namespace App\MessageHandler;

use App\Entity\WatchList;
use App\Message\ProcessWatchListsTrigger;
use App\Message\UpdateDomainsFromWatchlist;
use App\Repository\WatchListRepository;
use Random\Randomizer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessWatchListsTriggerHandler
{
    public function __construct(
        private WatchListRepository $watchListRepository,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessWatchListsTrigger $message): void
    {
        /*
         * We shuffle the watch lists to process them in an order that we consider random.
         * The shuffling is provided by a high-level API of a CSPRNG number generator.
         */

        $randomizer = new Randomizer();
        $watchLists = $randomizer->shuffleArray($this->watchListRepository->findAll());

        /** @var WatchList $watchList */
        foreach ($watchLists as $watchList) {
            $this->bus->dispatch(new UpdateDomainsFromWatchlist($watchList->getToken()));
        }
    }
}
