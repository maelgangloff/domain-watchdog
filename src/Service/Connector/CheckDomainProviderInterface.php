<?php

namespace App\Service\Connector;

interface CheckDomainProviderInterface
{
    public function checkDomains(string ...$domains): array;
}
