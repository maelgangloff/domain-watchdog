<?php
namespace App\Service\Connector;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Domain;

class NamecheapConnector implements ConnectorInterface
{

    const BASE_URL = 'https://api.namecheap.com/xml.response';

    const SANDBOX_BASE_URL = 'http://www.sandbox.namecheap.com';

    public function __construct(private array $authData, private HttpClientInterface $client)
    {}

    public function orderDomain(Domain $domain, $dryRun): void
    {}

    private function call(string $command, array $parameters, array $authData = null): object
    {
        if (is_null($authData)) {
            $authData = $this->authData;
        }

        $actualParams = array_merge([
            'ApiUser' => $authData['ApiUser'],
            'ApiKey' => $authData['ApiKey']
        ], $parameters);

        $response = $this->client->request('POST', BASE_URL, [
            'query' => $actualParams
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

