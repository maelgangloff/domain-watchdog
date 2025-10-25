<?php

namespace App\MessageHandler;

use App\Entity\Watchlist;
use App\Message\ProcessWatchlistTrigger;
use App\Message\UpdateDomainsFromWatchlist;
use App\Repository\WatchlistRepository;
use Random\Randomizer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessWatchlistTriggerHandler
{
    public function __construct(
        private WatchlistRepository $watchlistRepository,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessWatchlistTrigger $message): void
    {
        /*
         * We shuffle the watch lists to process them in an order that we consider random.
         * The shuffling is provided by a high-level API of a CSPRNG number generator.
         */

        $randomizer = new Randomizer();
        $watchlists = $randomizer->shuffleArray($this->watchlistRepository->getEnabledWatchlist());

        /** @var Watchlist $watchlist */
        foreach ($watchlists as $watchlist) {
            $this->bus->dispatch(new UpdateDomainsFromWatchlist($watchlist->getToken()));
        }
    }
}
