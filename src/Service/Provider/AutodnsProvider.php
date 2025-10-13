<?php

namespace App\Service\Provider;

use App\Dto\Connector\AutodnsProviderDto;
use App\Dto\Connector\DefaultProviderDto;
use App\Entity\Domain;
use App\Exception\Provider\InvalidLoginException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class AutodnsProvider extends AbstractProvider
{
    protected string $dtoClass = AutodnsProviderDto::class;

    /** @var AutodnsProviderDto */
    protected DefaultProviderDto $authData;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        DenormalizerInterface&NormalizerInterface $serializer,
        private readonly HttpClientInterface $client,
        ValidatorInterface $validator,
    ) {
        parent::__construct($cacheItemPool, $serializer, $validator);
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
                ->setAuthBasic($this->authData->username, $this->authData->password)
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', (string) $this->authData->context)
                ->setBaseUri(self::BASE_URL)
                ->setJson([
                    'name' => $ldhName,
                    'ownerc' => [
                        'id' => $this->authData->contactid,
                    ],
                    'adminc' => [
                        'id' => $this->authData->contactid,
                    ],
                    'techc' => [
                        'id' => $this->authData->contactid,
                    ],
                    'confirmOrder' => $this->authData->ownerConfirm,
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
                ->setAuthBasic($authData->username, $authData->password)
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', (string) $authData->context)
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
                    ->setAuthBasic($authData->username, $authData->password)
                    ->setHeader('Accept', 'application/json')
                    ->setHeader('X-Domainrobot-Context', (string) $authData->context)
                    ->setBaseUri(self::BASE_URL)
                    ->setJson([
                        'origin' => $ldhName,
                        'main' => [
                            'address' => null, // $authData['dns_ip'],
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
     * @throws InvalidLoginException
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request(
            'GET',
            '/v1/hello',
            (new HttpOptions())
                ->setAuthBasic($this->authData->username, $this->authData->password)
                ->setHeader('Accept', 'application/json')
                ->setHeader('X-Domainrobot-Context', (string) $this->authData->context)
                ->setBaseUri(self::BASE_URL)
                ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw InvalidLoginException::fromIdentifier($this->authData->username);
        }
    }
}
