<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
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
    public function __construct(CacheItemPoolInterface $cacheItemPool, private readonly HttpClientInterface $client)
    {
        parent::__construct($cacheItemPool);
    }

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
        $ldhName = $domain->getLdhName();
        if (!$ldhName) {
            throw new \InvalidArgumentException('Domain name cannot be null');
        }

        if ($dryRun) {
            return;
        }

        $this->client->request(
            'POST',
            '/v1/domain',
            (new HttpOptions())
                ->setAuthBasic($this->authData['username'], $this->authData['password'])
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', $this->authData['context'])
                ->setBaseUri(self::BASE_URL)
                ->setJson([
                    'name' => $ldhName,
                    'ownerc' => [
                        'id' => $this->authData['contactid'],
                    ],
                    'adminc' => [
                        'id' => $this->authData['contactid'],
                    ],
                    'techc' => [
                        'id' => $this->authData['contactid'],
                    ],
                    'confirmOrder' => $this->authData['ownerConfirm'],
                    'nameServers' => [
                        [
                            'name' => 'a.ns14.net',
                        ],
                        [
                            'name' => 'b.ns14.net',
                        ],
                        [
                            'name' => 'c.ns14.net',
                        ],
                        [
                            'name' => 'd.ns14.net',
                        ],
                    ],
                ])
                ->toArray()
        )->toArray();
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function registerZone(Domain $domain, bool $dryRun = false): void
    {
        $authData = $this->authData;

        $ldhName = $domain->getLdhName();

        if ($dryRun) {
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
                        ],
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
                            'address' => $authData['dns_ip'],
                        ],
                        'soa' => [
                            'refresh' => 3600,
                            'retry' => 7200,
                            'expire' => 604800,
                            'ttl' => 600,
                        ],
                        'action' => 'COMPLETE',
                        'wwwInclude' => true,
                        'nameServers' => [
                            [
                                'name' => 'a.ns14.net',
                            ],
                            [
                                'name' => 'b.ns14.net',
                            ],
                            [
                                'name' => 'c.ns14.net',
                            ],
                            [
                                'name' => 'd.ns14.net',
                            ],
                        ],
                    ])
                    ->toArray()
            )->toArray();
        }
    }

    public function verifyAuthData(array $authData): array
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
            || true !== $authData['ownerConfirm']
            || true !== $ownerLegalAge
            || true !== $waiveRetractationPeriod
        ) {
            throw new HttpException(451, 'The user has not given explicit consent');
        }

        return [
            'username' => $authData['username'],
            'password' => $authData['password'],
            'acceptConditions' => $authData['acceptConditions'],
            'ownerLegalAge' => $authData['ownerLegalAge'],
            'ownerConfirm' => $authData['ownerConfirm'],
            'waiveRetractationPeriod' => $authData['waiveRetractationPeriod'],
            'context' => $authData['context'],
        ];
    }

    public function isSupported(Domain ...$domainList): bool
    {
        return true;
    }

    protected function getSupportedTldList(): array
    {
        return [];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.autodns.supported-tld');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function assertAuthentication(): void
    {
        try {
            $response = $this->client->request(
                'GET',
                '/v1/hello',
                (new HttpOptions())
                    ->setAuthBasic($this->authData['username'], $this->authData['password'])
                    ->setHeader('Accept', 'application/json')
                    ->setHeader('X-Domainrobot-Context', $this->authData['context'])
                    ->setBaseUri(self::BASE_URL)
                    ->toArray()
            );
        } catch (\Exception) {
            throw new BadRequestHttpException('Invalid Login');
        }

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new BadRequestHttpException('The status of these credentials is not valid');
        }
    }
}
