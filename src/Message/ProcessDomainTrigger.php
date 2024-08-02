<?php

namespace App\Message;

final class ProcessDomainTrigger
{
    public function __construct(
        public string $watchListToken,
        public string $ldhName,
        public \DateTimeImmutable $updatedAt
    ) {
    }
}
