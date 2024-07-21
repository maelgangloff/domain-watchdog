<?php

namespace App\Message;

use App\Entity\WatchList;

final readonly class ProcessWatchListTrigger
{

    public function __construct(
        public string $watchListToken,
    )
    {
    }
}
