<?php

namespace App\Config;

use App\Service\Connector\OvhConnector;
use App\Service\Connector\GandiConnector;
use App\Service\Connector\NamecheapConnector;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';
    case NAMECHEAP = 'namecheap';

    public function getConnectorProvider(): string
    {
        return match ($this) {
            ConnectorProvider::OVH => OvhConnector::class,
            ConnectorProvider::GANDI => GandiConnector::class,
            ConnectorProvider::NAMECHEAP => NamecheapConnector::class
        };
    }
}
