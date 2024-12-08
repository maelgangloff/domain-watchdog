<?php

namespace App\Service;

use App\Config\TriggerAction;
use App\Entity\Connector;
use App\Entity\Domain;
use App\Entity\RdapServer;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class InfluxdbService
{
    private Client $client;

    public function __construct(
        #[Autowire(param: 'influxdb_url')]
        private string $influxdbUrl = 'http://influxdb:8086',
        #[Autowire(param: 'influxdb_token')]
        private string $influxdbToken = '',
        #[Autowire(param: 'influxdb_bucket')]
        private string $influxdbBucket = 'domainwatchdog',
        #[Autowire(param: 'influxdb_org')]
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

    public function addRdapQueryPoint(RdapServer $rdapServer, string $ldhName, array $info): void
    {
        $this->writePoints(new Point('rdap_query', [
            'ldh_name' => $ldhName,
            'tld' => $rdapServer->getTld()->getTld(),
            'rdap_server' => $rdapServer->getUrl(),
            'primary_ip' => $info['primary_ip'],
            'http_code' => $info['http_code'],
        ], [
            'total_time_us' => $info['total_time_us'],
            'namelookup_time_us' => $info['namelookup_time_us'],
            'connect_time_us' => $info['connect_time_us'],
            'starttransfer_time_us' => $info['starttransfer_time_us'],
            'size_download' => $info['size_download'],
            'ssl_verify_result' => $info['ssl_verify_result'],
        ], (int) floor($info['start_time'] * 1e3),
            WritePrecision::MS)
        );
    }

    public function addDomainOrderPoint(Connector $connector, Domain $domain, bool $success): void
    {
        $this->writePoints(new Point('domain_order', [
            'domain' => $domain->getLdhName(),
            'tld' => $domain->getTld()->getTld(),
            'provider' => $connector->getProvider()->value,
        ], [
            'success' => $success,
        ]));
    }

    public function addDomainNotificationPoint(Domain $domain, TriggerAction $triggerAction, bool $success): void
    {
        $this->writePoints(new Point('domain_notification', [
            'domain' => $domain->getLdhName(),
            'tld' => $domain->getTld()->getTld(),
            'medium' => $triggerAction->value,
        ], [
            'success' => $success,
        ]));
    }

    private function writePoints(Point ...$points): void
    {
        $writeApi = $this->client->createWriteApi(['writeType' => WriteType::BATCHING, 'batchSize' => count($points)]);
        foreach ($points as $point) {
            $writeApi->write($point);
        }

        $writeApi->close();
    }
}
