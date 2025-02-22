<?php

namespace App\Service\Connector;

interface EppClientProviderInterface
{
    public function checkDomains(string ...$domains): array;
}
