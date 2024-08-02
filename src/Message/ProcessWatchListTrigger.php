<?php

namespace App\Message;

final readonly class ProcessWatchListTrigger
{
    public function __construct(
        public string $watchListToken,
    ) {
    }
}
