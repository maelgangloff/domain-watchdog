<?php

namespace App\Message;

use App\Entity\Domain;
use App\Entity\WatchList;
use DateTimeImmutable;

final class ProcessDomainTrigger
{
    public function __construct(
        public string         $watchListToken,
        public string            $ldhName,
        public DateTimeImmutable $updatedAt
    )
    {
    }
}
