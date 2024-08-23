<?php

namespace App\Service\Connector;

abstract class AbstractConnector implements ConnectorInterface
{
    protected array $authData;

    public function authenticate(array $authData)
    {
        $this->authData = $authData;
    }
}
