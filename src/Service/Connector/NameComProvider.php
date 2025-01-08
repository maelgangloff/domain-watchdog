<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class NameComProvider extends AbstractProvider
{
    public function __construct(CacheItemPoolInterface $cacheItemPool,
        private readonly HttpClientInterface $client,
        private readonly KernelInterface $kernel)
    {
        parent::__construct($cacheItemPool);
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
            '/v4/domains',
            (new HttpOptions())
                ->setHeader('Accept', 'application/json')
                ->setAuthBasic($this->authData['username'], $this->authData['token'])
                ->setBaseUri($dryRun ? self::DEV_BASE_URL : self::BASE_URL)
                ->setJson([
                    'domain' => [
                        [
                            'domainName' => $domain->getLdhName(),
                            'locked' => false,
                            'autorenewEnabled' => false,
                        ],
                        'purchaseType' => 'registration',
                        'years' => 1,
                        // 'tldRequirements' => []
                    ],
                ])
                ->toArray()
        )->toArray();
    }

    public function verifySpecificAuthData(array $authData): array
    {
        $username = $authData['username'];
        $token = $authData['token'];

        if (
            !is_string($username) || empty($username)
            || !is_string($token) || empty($token)
        ) {
            throw new BadRequestHttpException('Bad authData schema');
        }

        return [
            'username' => $authData['username'],
            'token' => $authData['token'],
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
        return $this->cacheItemPool->getItem('app.provider.namecom.supported-tld');
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function assertAuthentication(): void
    {
        try {
            $response = $this->client->request(
                'GET',
                '/v4/hello',
                (new HttpOptions())
                    ->setHeader('Accept', 'application/json')
                    ->setAuthBasic($this->authData['username'], $this->authData['token'])
                    ->setBaseUri($this->kernel->isDebug() ? self::DEV_BASE_URL : self::BASE_URL)
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
