<?php

namespace App\Exception;

class DomainNotFoundException extends \Exception
{
    public static function fromDomain(string $ldhName): self
    {
        return new self("The domain name $ldhName is not present in the WHOIS database");
    }
}
