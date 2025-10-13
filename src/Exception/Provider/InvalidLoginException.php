<?php

namespace App\Exception\Provider;

class InvalidLoginException extends AbstractProviderException
{
    public function __construct(string $message = '')
    {
        parent::__construct('' === $message ? 'The status of these credentials is not valid' : $message);
    }

    public static function fromIdentifier(string $identifier): self
    {
        return new self("Invalid login for identifier $identifier");
    }
}
