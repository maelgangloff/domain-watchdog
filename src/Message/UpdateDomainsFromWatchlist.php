<?php

namespace App\Message;

final readonly class UpdateDomainsFromWatchlist
{
    public function __construct(
        public string $watchlistToken,
    ) {
    }
}
