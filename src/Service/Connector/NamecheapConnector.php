<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
class NamecheapConnector extends AbstractConnector
{
    public const BASE_URL = 'https://api.namecheap.com/xml.response';

    public const SANDBOX_BASE_URL = 'http://api.sandbox.namecheap.com/xml.response';

    public function __construct(private HttpClientInterface $client, private readonly string $outgoingIp)
    {
    }

    public function orderDomain(Domain $domain, $dryRun): void
    {
        $addressesRes = $this->call('namecheap.users.address.getList', [], $dryRun);
        $addresses = $addressesRes->AddressGetListResult->List;

        if (count($addresses) < 1) {
            throw new \Exception('Namecheap account requires at least one address to purchase a domain');
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
            'DomainName' => $domain->getLdhName(),
            'Years' => 1,
            'AddFreeWhoisguard' => 'yes',
            'WGEnabled' => 'yes',
        ], $domainAddresses), $dryRun);
    }

    private static function mergePrefixKeys(string $prefix, array|object $src, array &$dest)
    {
        foreach ($src as $key => $value) {
            $dest[$prefix.$key] = $value;
        }
    }

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
            throw new \Exception($data->Errors->Error); // FIXME better exception type
        }

        return $data->CommandResponse;
    }

    public static function verifyAuthData(array $authData, HttpClientInterface $client): array
    {
        return $authData;
    }
}
