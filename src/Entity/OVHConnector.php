<?php

namespace App\Entity;

use App\Config\ConnectorInterface;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class OVHConnector extends Connector implements ConnectorInterface
{

}