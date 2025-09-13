<?php

namespace App\Message;

final class ProposeDomainMessage
{
    public function __construct(
        public string $ldhName,
    ) {
    }
}
