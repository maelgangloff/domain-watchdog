<?php

namespace App\Exception;

use Symfony\Component\HttpClient\Exception\ServerException;

class RdapServerException extends \Exception
{
    public static function fromServerException(ServerException $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
}
