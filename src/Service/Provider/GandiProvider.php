<?php

namespace App\Service\Provider;

use App\Dto\Connector\DefaultProviderDto;
use App\Dto\Connector\GandiProviderDto;
use App\Entity\Domain;
use App\Exception\Provider\DomainOrderFailedExeption;
use App\Exception\Provider\InvalidLoginException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
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
class GandiProvider extends AbstractProvider
{
    protected string $dtoClass = GandiProviderDto::class;

    /** @var GandiProviderDto */
    protected DefaultProviderDto $authData;

    private const BASE_URL = 'https://api.gandi.net';

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        private readonly HttpClientInterface $client,
        DenormalizerInterface&NormalizerInterface $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($cacheItemPool, $serializer, $validator);
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
            ->setAuthBearer($this->authData->token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        )->toArray();

        $httpOptions = (new HttpOptions())
            ->setAuthBearer($this->authData->token)
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

        if ($this->authData->sharingId) {
            $httpOptions->setQuery([
                'sharing_id' => $this->authData->sharingId,
            ]);
        }

        $res = $this->client->request('POST', '/domain/domains', $httpOptions->toArray());

        if ((!$dryRun && Response::HTTP_ACCEPTED !== $res->getStatusCode())
            || ($dryRun && Response::HTTP_OK !== $res->getStatusCode())) {
            throw new DomainOrderFailedExeption($res->toArray()['message']);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws InvalidLoginException
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request('GET', '/v5/organization/user-info', (new HttpOptions())
            ->setAuthBearer($this->authData->token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new InvalidLoginException();
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
            ->setAuthBearer($this->authData->token)
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
        return $this->cacheItemPool->getItem('app.provider.gandi.supported-tld');
    }
}
