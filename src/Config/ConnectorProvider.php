<?php

namespace App\Config;

use App\Config\Provider\AutodnsProvider;
use App\Config\Provider\GandiProvider;
use App\Config\Provider\OvhProvider;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';
    case AUTODNS = 'autodns';

    public function getConnectorProvider(): string
    {
        return match ($this) {
            ConnectorProvider::OVH => OvhProvider::class,
            ConnectorProvider::GANDI => GandiProvider::class,
            ConnectorProvider::AUTODNS => AutodnsProvider::class,
        };
    }
}
