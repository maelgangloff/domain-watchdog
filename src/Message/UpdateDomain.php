<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('rdap_async')]
final class UpdateDomain
{
    public function __construct(
        public string $ldhName,
        public string $watchlistToken,
    ) {
    }
}
