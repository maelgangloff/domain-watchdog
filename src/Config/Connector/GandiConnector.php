<?php

namespace App\Config\Connector;

use App\Entity\Domain;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class GandiConnector implements ConnectorInterface
{
    private const BASE_URL = 'https://api.gandi.net/v5';

    public function __construct(private array $authData, private HttpClientInterface $client)
    {
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
        if (!$domain->getDeleted()) {
            throw new \Exception('The domain name still appears in the WHOIS database');
        }

        $ldhName = $domain->getLdhName();
        if (!$ldhName) {
            throw new \Exception('Domain name cannot be null');
        }

        $authData = self::verifyAuthData($this->authData, $this->client);

        $acceptConditions = $authData['acceptConditions'];
        $ownerLegalAge = $authData['ownerLegalAge'];
        $waiveRetractationPeriod = $authData['waiveRetractationPeriod'];

        if (false === $acceptConditions || false === $ownerLegalAge || false === $waiveRetractationPeriod) {
            throw new \Exception('It is not possible to order a domain name if the legal conditions are not met');
        }

        $user = $this->client->request('GET', '/v5/organization/user-info', (new HttpOptions())
            ->setAuthBearer($authData['token'])
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        )->toArray();

        $httpOptions = (new HttpOptions())
            ->setAuthBearer($authData['token'])
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

        if (array_key_exists('sharingId', $authData)) {
            $httpOptions->setQuery([
                'sharing_id' => $authData['sharingId'],
            ]);
        }

        $res = $this->client->request('POST', '/domain/domains', $httpOptions->toArray());

        if ((!$dryRun && Response::HTTP_ACCEPTED !== $res->getStatusCode())
            || ($dryRun && Response::HTTP_OK !== $res->getStatusCode())) {
            throw new \Exception($res->toArray()['message']);
        }
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    public static function verifyAuthData(array $authData, HttpClientInterface $client): array
    {
        $token = $authData['token'];

        $acceptConditions = $authData['acceptConditions'];
        $ownerLegalAge = $authData['ownerLegalAge'];
        $waiveRetractationPeriod = $authData['waiveRetractationPeriod'];

        if (!is_string($token) || empty($token)
            || (array_key_exists('sharingId', $authData) && !is_string($authData['sharingId']))
            || true !== $acceptConditions
            || true !== $ownerLegalAge
            || true !== $waiveRetractationPeriod
        ) {
            throw new \Exception('Bad authData schema');
        }

        $response = $client->request('GET', '/v5/organization/user-info', (new HttpOptions())
            ->setAuthBearer($token)
            ->setHeader('Accept', 'application/json')
            ->setBaseUri(self::BASE_URL)
            ->toArray()
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new \Exception('The status of these credentials is not valid');
        }

        $authDataReturned = [
            'token' => $token,
            'acceptConditions' => $acceptConditions,
            'ownerLegalAge' => $ownerLegalAge,
            'waiveRetractationPeriod' => $waiveRetractationPeriod,
        ];

        if (array_key_exists('sharingId', $authData)) {
            $authDataReturned['sharingId'] = $authData['sharingId'];
        }

        return $authDataReturned;
    }
}
