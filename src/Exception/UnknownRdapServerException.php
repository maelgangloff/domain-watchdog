<?php

namespace App\Exception;

class UnknownRdapServerException extends \Exception
{
    public static function fromTld(string $tld): UnknownRdapServerException
    {
        return new UnknownRdapServerException("TLD $tld: Unable to determine which RDAP server to contact");
    }
}
