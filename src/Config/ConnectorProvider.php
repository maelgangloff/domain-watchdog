<?php

namespace App\Config;

use App\Service\Provider\AutodnsProvider;
use App\Service\Provider\EppClientProvider;
use App\Service\Provider\GandiProvider;
use App\Service\Provider\NamecheapProvider;
use App\Service\Provider\NameComProvider;
use App\Service\Provider\OpenProviderProvider;
use App\Service\Provider\OvhProvider;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';
    case AUTODNS = 'autodns';
    case NAMECHEAP = 'namecheap';
    case NAMECOM = 'namecom';
    case OPENPROVIDER = 'openprovider';
    case EPP = 'epp';

    public function getConnectorProvider(): string
    {
        return match ($this) {
            ConnectorProvider::OVH => OvhProvider::class,
            ConnectorProvider::GANDI => GandiProvider::class,
            ConnectorProvider::AUTODNS => AutodnsProvider::class,
            ConnectorProvider::NAMECHEAP => NamecheapProvider::class,
            ConnectorProvider::NAMECOM => NameComProvider::class,
            ConnectorProvider::EPP => EppClientProvider::class,
            ConnectorProvider::OPENPROVIDER => OpenProviderProvider::class,
        };
    }
}
