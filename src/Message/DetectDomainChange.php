<?php

namespace App\Message;

final class DetectDomainChange
{
    public function __construct(
        public string $watchlistToken,
        public string $ldhName,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
