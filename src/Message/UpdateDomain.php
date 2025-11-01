<?php

namespace App\Message;

final class UpdateDomain
{
    public function __construct(
        public string $ldhName,
        public ?string $watchlistToken,
        public bool $onlyNew = false,
    ) {
    }
}
