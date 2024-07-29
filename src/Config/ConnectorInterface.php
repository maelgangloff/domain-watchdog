<?php

namespace App\Config;

use App\Entity\Domain;

interface ConnectorInterface
{
    public function orderDomain(Domain $domain,
                                bool   $acceptConditions,
                                bool   $ownerLegalAge,
                                bool   $waiveRetractationPeriod,
                                bool   $dryRyn
    ): void;
}