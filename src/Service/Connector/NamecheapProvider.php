<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class NamecheapProvider extends AbstractProvider
{
    public const BASE_URL = 'https://api.namecheap.com/xml.response';

    public const SANDBOX_BASE_URL = 'https://api.sandbox.namecheap.com/xml.response';

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        private readonly HttpClientInterface $client,
        private readonly string $outgoingIp,
    ) {
        parent::__construct($cacheItemPool);
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    public function orderDomain(Domain $domain, $dryRun): void
    {
        $addresses = $this->call('namecheap.users.address.getList', [], $dryRun)->AddressGetListResult->List;

        if (count($addresses) < 1) {
            throw new BadRequestHttpException('Namecheap account requires at least one address to purchase a domain');
        }

        $addressId = (string) $addresses->attributes()['AddressId'];
        $address = (array) $this->call('namecheap.users.address.getinfo', ['AddressId' => $addressId], $dryRun)->GetAddressInfoResult;

        if (empty($address['PostalCode'])) {
            $address['PostalCode'] = $address['Zip'];
        }

        $domainAddresses = [];

        self::mergePrefixKeys('Registrant', $address, $domainAddresses);
        self::mergePrefixKeys('Tech', $address, $domainAddresses);
        self::mergePrefixKeys('Admin', $address, $domainAddresses);
        self::mergePrefixKeys('AuxBilling', $address, $domainAddresses);

        $this->call('namecheap.domains.create', array_merge([
            'DomainName' => $domain->getLdhName(), // Domain name to register
            'Years' => 1, // Number of years to register
            'AddFreeWhoisguard' => 'yes', // Adds free domain privacy for the domain
            'WGEnabled' => 'yes', // Enables free domain privacy for the domain
        ], $domainAddresses), $dryRun);
    }

    private static function mergePrefixKeys(string $prefix, array|object $src, array &$dest): void
    {
        foreach ($src as $key => $value) {
            $dest[$prefix.$key] = $value;
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    private function call(string $command, array $parameters = [], bool $dryRun = true): object
    {
        $actualParams = array_merge([
            'Command' => $command,
            'UserName' => $this->authData['ApiUser'],
            'ApiUser' => $this->authData['ApiUser'],
            'ApiKey' => $this->authData['ApiKey'],
            'ClientIp' => $this->outgoingIp,
        ], $parameters);

        $response = $this->client->request('POST', $dryRun ? self::SANDBOX_BASE_URL : self::BASE_URL, [
            'query' => $actualParams,
        ]);

        $data = new \SimpleXMLElement($response->getContent());

        if ($data->Errors->Error) {
            throw new BadRequestHttpException($data->Errors->Error);
        }

        return $data->CommandResponse;
    }

    public function verifySpecificAuthData(array $authData): array
    {
        foreach (['ApiUser', 'ApiKey'] as $key) {
            if (empty($authData[$key]) || !is_string($authData[$key])) {
                throw new BadRequestHttpException("Bad authData schema: missing or invalid '$key'");
            }
        }

        return [
            'ApiUser' => $authData['ApiUser'],
            'ApiKey' => $authData['ApiKey'],
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function assertAuthentication(): void
    {
        $addresses = $this->call('namecheap.users.address.getList', [], false)->AddressGetListResult->List;

        if (count($addresses) < 1) {
            throw new BadRequestHttpException('Namecheap account requires at least one address to purchase a domain');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.namecheap.supported-tld');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function getSupportedTldList(): array
    {
        $supported = [];

        $tlds = $this->call('namecheap.domains.gettldlist', [], false)->Tlds->Tld;

        for ($i = 0; $i < $tlds->count(); ++$i) {
            $supported[] = (string) $tlds[$i]['Name'];
        }

        return $supported;
    }
}
