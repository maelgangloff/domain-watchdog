<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NamecheapConnector extends AbstractConnector
{
    public const BASE_URL = 'https://api.namecheap.com/xml.response';

    public const SANDBOX_BASE_URL = 'http://api.sandbox.namecheap.com/xml.response';

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function orderDomain(Domain $domain, $dryRun): void
    {
        $addresses = $this->call('namecheap.users.address.getList');

        if (count($addresses) < 1) {
            throw new \Exception('Namecheap account requires at least one address to purchase a domain');
        }

        $addressId = $addresses->{0}->AddressId;
        $address = $this->call('namecheap.users.address.getinfo', ['AddressId' => $addressId]);

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
        ], $domainAddresses));
    }

    private static function mergePrefixKeys(string $prefix, array|object $src, array &$dest)
    {
        foreach ($src as $key => $value) {
            $dest[$prefix.$key] = $value;
        }
    }

    private function call(string $command, array $parameters = [], ?array $authData = null): object
    {
        if (is_null($authData)) {
            $authData = $this->authData;
        }

        $actualParams = array_merge([
            'Command' => $command,
            'ApiUser' => $authData['ApiUser'],
            'ApiKey' => $authData['ApiKey'],
            'ClientIp' => '', // TODO DW instance IP envvar
        ], $parameters);

        $response = $this->client->request('POST', self::BASE_URL, [
            'query' => $actualParams,
        ]);

        $data = new \SimpleXMLElement($response->getContent());

        if ($data->errors->error) {
            throw new \Exception(implode(', ', $data->errors->error)); // FIXME better exception type
        }

        return $data->CommandResponse;
    }

    public static function verifyAuthData(array $authData, HttpClientInterface $client): array
    {
    }
}
