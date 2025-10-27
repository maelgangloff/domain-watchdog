<?php

namespace App\Message;

final class SendDomainEventNotif
{
    public function __construct(
        public string $watchlistToken,
        public string $ldhName,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
