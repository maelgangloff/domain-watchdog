<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
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

#[Autoconfigure(public: true)]
class GandiProvider extends AbstractProvider
{
    private const BASE_URL = 'https://api.gandi.net';

    public function __construct(CacheItemPoolInterface $cacheItemPool, private readonly HttpClientInterface $client)
    {
        parent::__construct($cacheItemPool);
    }

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

        $user = $this->client->request('GET', '/v5/organization/user-info', (new HttpOptions())
            ->setAuthBearer($this->authData['token'])
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        )->toArray();

        $httpOptions = (new HttpOptions())
            ->setAuthBearer($this->authData['token'])
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->setHeader('Dry-Run', $dryRun ? '1' : '0')
            ->setJson([
                'fqdn' => $ldhName,
                'owner' => [
                    'email' => $user['email'],
                    'given' => $user['firstname'],
                    'family' => $user['lastname'],
                    'streetaddr' => $user['streetaddr'],
                    'zip' => $user['zip'],
                    'city' => $user['city'],
                    'state' => $user['state'],
                    'phone' => $user['phone'],
                    'country' => $user['country'],
                    'type' => 'individual',
                ],
                'tld_period' => 'golive',
            ]);

        if (array_key_exists('sharingId', $this->authData)) {
            $httpOptions->setQuery([
                'sharing_id' => $this->authData['sharingId'],
            ]);
        }

        $res = $this->client->request('POST', '/domain/domains', $httpOptions->toArray());

        if ((!$dryRun && Response::HTTP_ACCEPTED !== $res->getStatusCode())
            || ($dryRun && Response::HTTP_OK !== $res->getStatusCode())) {
            throw new HttpException($res->toArray()['message']);
        }
    }

    public function verifySpecificAuthData(array $authData): array
    {
        $token = $authData['token'];

        if (!is_string($token) || empty($token)
            || (array_key_exists('sharingId', $authData) && !is_string($authData['sharingId']))
        ) {
            throw new BadRequestHttpException('Bad authData schema');
        }

        $authDataReturned = [
            'token' => $token,
        ];

        if (array_key_exists('sharingId', $authData)) {
            $authDataReturned['sharingId'] = $authData['sharingId'];
        }

        return $authDataReturned;
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request('GET', '/v5/organization/user-info', (new HttpOptions())
            ->setAuthBearer($this->authData['token'])
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new BadRequestHttpException('The status of these credentials is not valid');
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function getSupportedTldList(): array
    {
        $response = $this->client->request('GET', '/v5/domain/tlds', (new HttpOptions())
            ->setAuthBearer($this->authData['token'])
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray())->toArray();

        return array_map(fn ($tld) => $tld['name'], $response);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.ovh.supported-tld');
    }
}
