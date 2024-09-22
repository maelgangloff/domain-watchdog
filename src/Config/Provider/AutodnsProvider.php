<?php

namespace App\Config\Provider;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AutodnsProvider extends AbstractProvider
{
    private const BASE_URL = 'https://api.autodns.com';

    /**
     * Order a domain name with the Gandi API.
     *
     * @throws \Exception
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function orderDomain(Domain $domain, bool $dryRun = false): void
    {
        if (!$domain->getDeleted()) {
            throw new \InvalidArgumentException('The domain name still appears in the WHOIS database');
        }

        $ldhName = $domain->getLdhName();
        if (!$ldhName) {
            throw new \InvalidArgumentException('Domain name cannot be null');
        }

        $authData = self::verifyAuthData($this->authData, $this->client);

        if($dryRun) {
            return;
        }

        $this->client->request(
            'POST',
            '/v1/domain',
            (new HttpOptions())
                ->setAuthBasic($authData['username'], $authData['password'])
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', $authData['context'])

                ->setBaseUri(self::BASE_URL)
                ->setJson([
                    'name' => $ldhName,
                    'ownerc' => [
                        'id' => $authData['contactid'],
                    ],
                    'adminc' => [
                        'id' => $authData['contactid'],
                    ],
                    'techc' => [
                        'id' => $authData['contactid'],
                    ],
                    'confirmOrder' => $authData['ownerConfirm'],
                    'nameServers' => [
                        [
                            'name' => 'a.ns14.net'
                        ],
                        [
                            'name' => 'b.ns14.net'
                        ],
                        [
                            'name' => 'c.ns14.net'
                        ],
                        [
                            'name' => 'd.ns14.net'
                        ],
                    ]
                ])
                ->toArray()
        )->toArray();
    }

    public function registerZone(Domain $domain, bool $dryRun = false): void
    {
        $authData = $this->authData;

        $ldhName = $domain->getLdhName();

        if($dryRun) {
            return;
        }

        $zoneCheck = $this->client->request(
            'POST',
            '/v1/zone/_search?keys=name',
            (new HttpOptions())
                ->setAuthBasic($authData['username'], $authData['password'])
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', $authData['context'])

                ->setBaseUri(self::BASE_URL)
                ->setJson([
                    'filters' => [
                        [
                            'key' => 'name',
                            'value' => $ldhName,
                            'operator' => 'EQUAL',
                        ]
                    ],
                ])
                ->toArray()
        )->toArray();

        $responseDataIsEmpty = empty($zoneCheck['data']);

        if ($responseDataIsEmpty) {
            // The domain not yet exists in DNS Server, we create them

            $this->client->request(
                'POST',
                '/v1/zone',
                (new HttpOptions())
                    ->setAuthBasic($authData['username'], $authData['password'])
                    ->setHeader('Accept', 'application/json')
                    ->setHeader('X-Domainrobot-Context', $authData['context'])

                    ->setBaseUri(self::BASE_URL)
                    ->setJson([
                        'origin' => $ldhName,
                        'main' => [
                            'address' => $authData['dns_ip']
                        ],
                        'soa' => [
                            'refresh' => 3600,
                            'retry' => 7200,
                            'expire' => 604800,
                            'ttl' => 600
                        ],
                        'action' => 'COMPLETE',
                        'wwwInclude' => true,
                        'nameServers' => [
                            [
                                'name' => 'a.ns14.net'
                            ],
                            [
                                'name' => 'b.ns14.net'
                            ],
                            [
                                'name' => 'c.ns14.net'
                            ],
                            [
                                'name' => 'd.ns14.net'
                            ],
                        ]

                    ])
                    ->toArray()
            )->toArray();
        }
    }
    
    /**
     * @throws TransportExceptionInterface
     */
    public static function verifyAuthData(array $authData, HttpClientInterface $client): array
    {

        $username = $authData['username'];
        $password = $authData['password'];

        $acceptConditions = $authData['acceptConditions'];
        $ownerLegalAge = $authData['ownerLegalAge'];
        $waiveRetractationPeriod = $authData['waiveRetractationPeriod'];

        if (empty($authData['context'])) {
            $authData['context'] = 4;
        }

        if (
            empty($username) || empty($password)
        ) {
            throw new BadRequestHttpException('Bad authData schema');
        }

        if (
            true !== $acceptConditions
            || empty($authData['ownerConfirm'])
            || true !== $ownerLegalAge
            || true !== $waiveRetractationPeriod
        ) {
            throw new HttpException(451, 'The user has not given explicit consent');
        }

        try {
            $response = $client->request(
                'GET',
                '/v1/hello',
                (new HttpOptions())
                    ->setAuthBasic($authData['username'], $authData['password'])
                    ->setHeader('Accept', 'application/json')
                    ->setHeader('X-Domainrobot-Context', $authData['context'])
                    ->setBaseUri(self::BASE_URL)
                    ->toArray()
            );
        } catch (\Exception $exp) {
            throw new BadRequestHttpException('Invalid Login');
        }

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new BadRequestHttpException('The status of these credentials is not valid');
        }

        return $authData;
    }

    public function isSupported(Domain ...$domainList): bool
    {
        return true;
    }

    protected function getSupportedTldList(): array { 
        return [];
    }
    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.autodns.supported-tld');
    }
}
