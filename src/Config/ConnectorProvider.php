<?php

namespace App\Config;

use App\Config\Connector\GandiConnector;
use App\Config\Connector\OvhConnector;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';

    public function getConnectorProvider(): string
    {
        return match ($this) {
            ConnectorProvider::OVH => OvhConnector::class,
            ConnectorProvider::GANDI => GandiConnector::class
        };
    }
}
