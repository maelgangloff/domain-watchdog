<?php

namespace App\Message;

final class SendDomainEventNotif
{
    public function __construct(
        public string $watchListToken,
        public string $ldhName,
        public \DateTimeImmutable $updatedAt
    ) {
    }
}
