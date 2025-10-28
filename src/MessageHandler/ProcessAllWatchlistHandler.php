<?php

namespace App\MessageHandler;

use App\Entity\Watchlist;
use App\Message\ProcessAllWatchlist;
use App\Message\ProcessWatchlist;
use App\Repository\WatchlistRepository;
use Random\Randomizer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ProcessAllWatchlistHandler
{
    public function __construct(
        private WatchlistRepository $watchlistRepository,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ProcessAllWatchlist $message): void
    {
        /*
         * We shuffle the watch lists to process them in an order that we consider random.
         * The shuffling is provided by a high-level API of a CSPRNG number generator.
         *
         * ProcessAllWatchlist -> ProcessWatchlist -> UpdateDomain -> (DetectDomainChange & OrderDomain)
         */

        $randomizer = new Randomizer();
        $watchlists = $randomizer->shuffleArray($this->watchlistRepository->getEnabledWatchlist());

        /** @var Watchlist $watchlist */
        foreach ($watchlists as $watchlist) {
            $this->bus->dispatch(new ProcessWatchlist($watchlist->getToken()));
        }
    }
}
