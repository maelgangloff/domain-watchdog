<?php

namespace App\Exception;

class TldNotSupportedException extends \Exception
{
    public static function fromTld(string $tld): TldNotSupportedException
    {
        return new TldNotSupportedException("The requested TLD $tld is not yet supported, please try again with another one");
    }
}
