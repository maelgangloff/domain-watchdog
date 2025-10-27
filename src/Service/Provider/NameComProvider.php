<?php

namespace App\Service\Provider;

use App\Dto\Connector\DefaultProviderDto;
use App\Dto\Connector\NameComProviderDto;
use App\Entity\Domain;
use App\Exception\Provider\InvalidLoginException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class NameComProvider extends AbstractProvider
{
    protected string $dtoClass = NameComProviderDto::class;

    /** @var NameComProviderDto */
    protected DefaultProviderDto $authData;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        private readonly HttpClientInterface $client,
        private readonly KernelInterface $kernel,
        DenormalizerInterface&NormalizerInterface $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($cacheItemPool, $serializer, $validator);
    }

    private const BASE_URL = 'https://api.name.com';
    private const DEV_BASE_URL = 'https://api.dev.name.com';

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

        $this->client->request(
            'POST',
            '/core/v1/domains',
            (new HttpOptions())
                ->setHeader('Accept', 'application/json')
                ->setAuthBasic($this->authData->username, $this->authData->token)
                ->setBaseUri($dryRun ? self::DEV_BASE_URL : self::BASE_URL)
                ->setJson([
                    'domain' => [
                        'domainName' => $domain->getLdhName(),
                        'locked' => false,
                        'autorenewEnabled' => false,
                        'purchaseType' => 'registration',
                        // 'tldRequirements' => []
                    ],
                ])
                ->toArray()
        )->toArray();
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
        return $this->cacheItemPool->getItem('app.provider.namecom.supported-tld');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws InvalidLoginException
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request(
            'GET',
            '/core/v1/hello',
            (new HttpOptions())
                ->setHeader('Accept', 'application/json')
                ->setAuthBasic($this->authData->username, $this->authData->token)
                ->setBaseUri($this->kernel->isDebug() ? self::DEV_BASE_URL : self::BASE_URL)
                ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw InvalidLoginException::fromIdentifier($this->authData->username);
        }
    }
}
