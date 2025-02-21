<?php

namespace App\Service\Connector;

use App\Entity\Domain;

interface EppClientProviderInterface
{
    public function checkDomains(Domain ...$domains): array;
}
