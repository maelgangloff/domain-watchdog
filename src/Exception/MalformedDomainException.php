<?php

namespace App\Exception;

class MalformedDomainException extends \Exception
{
    public static function fromDomain(string $ldhName): MalformedDomainException
    {
        return new MalformedDomainException("Domain name ($ldhName) must contain at least one dot");
    }
}
