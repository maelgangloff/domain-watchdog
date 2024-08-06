<?php

namespace App\Config\Connector;

use App\Entity\Domain;

interface ConnectorInterface
{
    public function orderDomain(Domain $domain, bool $dryRun): void;
}
