<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface ConnectorInterface
{
    public function authenticate(array $authData);

    public function orderDomain(Domain $domain, bool $dryRun): void;

    public static function verifyAuthData(array $authData, HttpClientInterface $client): array;
}
