<?php

namespace App\Exception\Provider;

class PermissionErrorException extends AbstractProviderException
{
    public static function fromIdentifier(string $identifier): self
    {
        return new self("Not enough permissions for identifier $identifier");
    }
}
