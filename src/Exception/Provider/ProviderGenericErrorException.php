<?php

namespace App\Exception\Provider;

class ProviderGenericErrorException extends AbstractProviderException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
