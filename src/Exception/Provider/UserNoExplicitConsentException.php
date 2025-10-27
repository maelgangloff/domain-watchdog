<?php

namespace App\Exception\Provider;

class UserNoExplicitConsentException extends AbstractProviderException
{
    public function __construct()
    {
        parent::__construct('The user has not given explicit consent');
    }
}
