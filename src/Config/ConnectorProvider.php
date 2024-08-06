<?php

namespace App\Config;

enum ConnectorProvider: string
{
    case OVH = 'ovh';
    case GANDI = 'gandi';
}
