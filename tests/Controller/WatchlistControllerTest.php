<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Watchlist;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use App\Tests\State\WatchlistUpdateProcessorTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class WatchlistControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetWatchlistCollection(): void
    {
        $client = WatchlistUpdateProcessorTest::createUserAndWatchlist();

        $response = $client->request('GET', '/api/watchlists');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(Watchlist::class);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetTrackedDomains()
    {
        $client = WatchlistUpdateProcessorTest::createUserAndWatchlist(null, ['/api/domains/example.org']);
        $response = $client->request('GET', '/api/tracked');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetWatchlistFeeds()
    {
        $client = WatchlistUpdateProcessorTest::createUserAndWatchlist();

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
}
