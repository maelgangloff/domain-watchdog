<?php

namespace App\Exception\Provider;

class ExpiredLoginException extends AbstractProviderException
{
    public static function fromIdentifier(string $identifier): self
    {
        return new self("Expired login for identifier $identifier");
    }
}
