<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\WatchList;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class WatchlistControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    public function testGetWatchlistCollection(): void
    {
        $client = $this->createUserAndWatchlist();

        $response = $client->request('GET', '/api/watchlists');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(WatchList::class);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testCreateWatchlist(): void
    {
        $this->createUserAndWatchlist();
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesResourceItemJsonSchema(WatchList::class);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetTrackedDomains()
    {
        $client = $this->createUserAndWatchlist();

        $response = $client->request('GET', '/api/tracked');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetWatchlistFeeds()
    {
        $client = $this->createUserAndWatchlist();

        $response = $client->request('GET', '/api/watchlists');
        $token = $response->toArray()['hydra:member'][0]['token'];

        $client->request('GET', '/api/watchlists/'.$token.'/calendar');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/calendar; charset=utf-8');

        $client->request('GET', '/api/watchlists/'.$token.'/rss/events');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/atom+xml; charset=utf-8');

        $client->request('GET', '/api/watchlists/'.$token.'/rss/status');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/atom+xml; charset=utf-8');
    }

    private function createUserAndWatchlist(): Client
    {
        $client = self::createClientWithCredentials(self::getToken(UserFactory::createOne()));
        $client->request('POST', '/api/watchlists', ['json' => [
            'domains' => ['/api/domains/example.com'],
            'name' => 'My Watchlist',
            'triggers' => [
                ['action' => 'email', 'event' => 'last changed'],
                ['action' => 'email', 'event' => 'transfer'],
                ['action' => 'email', 'event' => 'expiration'],
                ['action' => 'email', 'event' => 'deletion'],
            ],
        ]]);

        return $client;
    }
}
