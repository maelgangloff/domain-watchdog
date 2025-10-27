<?php

namespace App\Exception;

class MalformedDomainException extends \Exception
{
    public static function fromDomain(string $ldhName): self
    {
        return new self("Malformed domain name ($ldhName)");
    }
}
