<?php

namespace App\Exception;

class MalformedDomainException extends \Exception
{
    public static function fromDomain(string $ldhName): self
    {
        return new self("Domain name ($ldhName) must contain at least one dot");
    }
}
