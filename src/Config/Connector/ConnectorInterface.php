<?php

namespace App\Config\Connector;

use App\Entity\Domain;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface ConnectorInterface
{
    public function __construct(array $authData, HttpClientInterface $client);

    public function orderDomain(Domain $domain, bool $dryRun): void;

    public static function verifyAuthData(array $authData, HttpClientInterface $client);
}
