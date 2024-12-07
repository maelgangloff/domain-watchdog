<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\RdapServer;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteType;

readonly class InfluxdbService
{
    private Client $client;

    public function __construct(
        private string $influxdbUrl = 'http://influxdb:8086',
        private string $influxdbToken = '',
        private string $influxdbBucket = 'domainwatchdog',
        private string $influxdbOrg = 'domainwatchdog',
    ) {
        $this->client = new Client([
            'url' => $this->influxdbUrl,
            'token' => $this->influxdbToken,
            'bucket' => $this->influxdbBucket,
            'org' => $this->influxdbOrg,
            'precision' => WritePrecision::MS,
        ]);
    }

    public function addRdapRequest(RdapServer $rdapServer, Domain $domain, bool $success): void
    {
        $this->writePoints(new Point('rdap_request', [
            'domain' => $domain->getLdhName(),
            'tld' => $domain->getTld()->getTld(),
            'rdap_server' => $rdapServer->getUrl(),
        ], [
            'success' => $success,
        ]));
    }

    public function writePoints(Point ...$points): void
    {
        $writeApi = $this->client->createWriteApi(['writeType' => WriteType::BATCHING, 'batchSize' => count($points)]);
        foreach ($points as $point) {
            $writeApi->write($point);
        }

        $writeApi->close();
    }
}
