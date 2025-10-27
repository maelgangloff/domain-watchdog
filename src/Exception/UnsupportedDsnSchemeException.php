<?php

namespace App\Exception;

class UnsupportedDsnSchemeException extends \Exception
{
    public static function fromScheme(string $scheme): UnsupportedDsnSchemeException
    {
        return new UnsupportedDsnSchemeException("The DSN scheme ($scheme) is not supported");
    }
}
