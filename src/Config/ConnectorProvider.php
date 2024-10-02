<?php

namespace App\Config;

use App\Service\Connector\AutodnsProvider;
use App\Service\Connector\GandiProvider;
use App\Service\Connector\NamecheapProvider;
use App\Service\Connector\OvhProvider;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';
    case AUTODNS = 'autodns';
    case NAMECHEAP = 'namecheap';

    public function getConnectorProvider(): string
    {
        return match ($this) {
            ConnectorProvider::OVH => OvhProvider::class,
            ConnectorProvider::GANDI => GandiProvider::class,
            ConnectorProvider::AUTODNS => AutodnsProvider::class,
            ConnectorProvider::NAMECHEAP => NamecheapProvider::class,
        };
    }
}
