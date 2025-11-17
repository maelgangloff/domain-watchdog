<?php

namespace App\Message;

final readonly class ProcessWatchlist
{
    public function __construct(
        public string $watchlistToken,
    ) {
    }
}
