<?php

namespace App\Service\Connector;

use App\Dto\Connector\DefaultProviderDto;
use App\Dto\Connector\OpenProviderProviderDto;
use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class OpenProviderProvider extends AbstractProvider
{
    protected string $dtoClass = OpenProviderProviderDto::class;

    /** @var OpenProviderProviderDto */
    protected DefaultProviderDto $authData;

    private const BASE_URL = 'https://api.openprovider.eu/v1beta';

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        private readonly HttpClientInterface $client,
        DenormalizerInterface&NormalizerInterface $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($cacheItemPool, $serializer, $validator);
    }

    /**
     * Order a domain name with the Open Provider API.
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

        $payload = [
            'admin_handle' => $this->authData->adminHandle,
            'billing_handle' => $this->authData->billingHandle,
            'owner_handle' => $this->authData->ownerHandle,
            'tech_handle' => $this->authData->techHandle,
            'domain' => [
                'name' => explode('.', $domain->getLdhName(), 2)[0],
                'extension' => explode('.', $domain->getLdhName(), 2)[1],
            ],
            'period' => '1',
            'ns_group' => $this->authData->nsGroup,
            'autorenew' => 'default',
        ];

        if (null !== $this->authData->resellerHandle) {
            $payload['resellerHandle'] = $this->authData->resellerHandle;
        }

        $res = $this->client->request('POST', '/domain', (new HttpOptions())
            ->setAuthBearer($this->authData->token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->setJson($payload)->toArray());

        if ((!$dryRun && Response::HTTP_ACCEPTED !== $res->getStatusCode())
            || ($dryRun && Response::HTTP_OK !== $res->getStatusCode())) {
            throw new HttpException($res->toArray()['message']);
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request('GET', '/customers', (new HttpOptions())
            ->setAuthBearer($this->authData->token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new BadRequestHttpException('The status of these credentials is not valid');
        }
    }

    protected function getSupportedTldList(): array
    {
        return [];
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.openprovider.supported-tld');
    }
}
