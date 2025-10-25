<?php

namespace App\Tests\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Watchlist;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class WatchlistUpdateProcessorTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testCreateWatchlist(): void
    {
        self::createUserAndWatchlist();
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceItemJsonSchema(Watchlist::class);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testCreateTwoWatchlistWithSameDomains(): void
    {
        $client = self::createClientWithCredentials(self::getToken(UserFactory::createOne()));
        self::createUserAndWatchlist($client);
        self::createUserAndWatchlist($client);
        $this->assertResponseStatusCodeSame(403);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testUpdateWatchlist(): void
    {
        $client = self::createUserAndWatchlist();
        $response = $client->request('GET', '/api/watchlists');
        $token = $response->toArray()['hydra:member'][0]['token'];

        $response = $client->request('PUT', '/api/watchlists/'.$token, ['json' => [
            'domains' => ['/api/domains/iana.org', '/api/domains/example.com'],
            'name' => 'My modified Watchlist',
            'trackedEvents' => ['last changed'],
            'trackedEppStatus' => [],
            'enabled' => true,
        ]]);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Watchlist::class);
        $data = $response->toArray();
        $this->assertCount(2, $data['domains']);
        $this->assertCount(1, $data['trackedEvents']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testPartialUpdateWatchlist(): void
    {
        $client = self::createUserAndWatchlist();
        $response = $client->request('GET', '/api/watchlists');
        $token = $response->toArray()['hydra:member'][0]['token'];

        $response = $client->request('PATCH', '/api/watchlists/'.$token, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'enabled' => false,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Watchlist::class);
        $data = $response->toArray();
        $this->assertFalse($data['enabled']);
    }

    public static function createUserAndWatchlist(?Client $client = null, array $domains = ['/api/domains/example.com'], ?string $connectorId = null): Client
    {
        $client = $client ?? self::createClientWithCredentials(self::getToken(UserFactory::createOne()));

        $client->request('POST', '/api/watchlists', ['json' => [
            'domains' => $domains,
            'name' => 'My Watchlist',
            'trackedEvents' => ['last changed', 'transfer', 'expiration', 'deletion'],
            'trackedEppStatus' => ['redemption period', 'pending delete', 'client hold', 'server hold'],
            'connector' => $connectorId,
            'enabled' => true,
        ]]);

        return $client;
    }
}
