<?php

namespace App\Service\Provider;

interface CheckDomainProviderInterface
{
    public function checkDomains(string ...$domains): array;
}
