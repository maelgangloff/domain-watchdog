<?php

namespace App\Service\Provider;

use App\Dto\Connector\DefaultProviderDto;
use App\Dto\Connector\OpenProviderProviderDto;
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
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class OpenProviderProvider extends AbstractProvider
{
    protected string $dtoClass = OpenProviderProviderDto::class;

    /** @var OpenProviderProviderDto */
    protected DefaultProviderDto $authData;

    private const string BASE_URL = 'https://api.openprovider.eu';

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
            'accept_eap_fee' => 0,
            'accept_premium_fee' => 0,
            // additional_data
            'admin_handle' => $this->authData->adminHandle,
            // application_mode
            // application_notice_id
            // application_smd
            // auth_code
            'autorenew' => 'default',
            'billing_handle' => $this->authData->billingHandle,
            'comments' => 'Ordered with Domain Watchdog',
            // dnssec_keys
            'domain' => [
                'name' => explode('.', $domain->getLdhName(), 2)[0],
                'extension' => explode('.', $domain->getLdhName(), 2)[1],
            ],
            // is_dnssec_enabled
            // is_easy_dmarc_enabled
            // is_private_whois_enabled
            // is_sectigo_dns_enabled
            // is_spamexperts_enabled
            // name_servers
            'ns_group' => $this->authData->nsGroup,
            // ns_template_id
            // ns_template_name
            'owner_handle' => $this->authData->ownerHandle,
            'period' => $this->authData->period,
            // promo_code
            // provider
            'tech_handle' => $this->authData->techHandle,
            // unit
            // use_domicile
        ];

        if (null !== $this->authData->resellerHandle) {
            $payload['resellerHandle'] = $this->authData->resellerHandle;
        }

        if ($dryRun) {
            return;
        }

        $res = $this->client->request('POST', '/v1beta/domain', (new HttpOptions())
            ->setAuthBearer($this->authData->token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->setJson($payload)->toArray());

        if (Response::HTTP_OK !== $res->getStatusCode()) {
            throw new DomainOrderFailedExeption($res->toArray()['message']);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws InvalidLoginException
     */
    protected function assertAuthentication(): void
    {
        $response = $this->client->request('GET', '/v1beta/customers', (new HttpOptions())
            ->setAuthBearer($this->authData->token)
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new InvalidLoginException();
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
