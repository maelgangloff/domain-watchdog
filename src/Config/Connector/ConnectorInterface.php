<?php

namespace App\Config\Connector;

use App\Entity\Domain;

interface ConnectorInterface
{
    public static function verifyAuthData(array $authData): array;

    public function orderDomain(Domain $domain, bool $dryRun): void;
}