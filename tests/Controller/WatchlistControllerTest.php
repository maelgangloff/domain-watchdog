<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\WatchList;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use App\Tests\State\WatchListUpdateProcessorTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class WatchlistControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetWatchlistCollection(): void
    {
        $client = WatchListUpdateProcessorTest::createUserAndWatchlist();

        $response = $client->request('GET', '/api/watchlists');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(WatchList::class);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetTrackedDomains()
    {
        $client = WatchListUpdateProcessorTest::createUserAndWatchlist();
        $response = $client->request('GET', '/api/tracked');

        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGetWatchlistFeeds()
    {
        $client = WatchListUpdateProcessorTest::createUserAndWatchlist();

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
