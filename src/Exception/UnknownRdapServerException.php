<?php

namespace App\Exception;

class UnknownRdapServerException extends \Exception
{
    public static function fromTld(string $tld): self
    {
        return new self("TLD $tld: Unable to determine which RDAP server to contact");
    }
}
