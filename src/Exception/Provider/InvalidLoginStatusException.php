<?php

namespace App\Exception\Provider;

class InvalidLoginStatusException extends AbstractProviderException
{
    public static function fromStatus(string $status): self
    {
        return new self("The status of these credentials is not valid ($status)");
    }
}
