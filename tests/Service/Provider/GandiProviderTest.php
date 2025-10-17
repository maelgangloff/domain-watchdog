<?php

namespace App\Tests\Service\Provider;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use App\Message\OrderDomain;
use App\MessageHandler\OrderDomainHandler;
use App\Repository\DomainRepository;
use App\Tests\Controller\ConnectorControllerTest;
use App\Tests\Service\RDAPServiceTest;
use App\Tests\State\WatchListUpdateProcessorTest;
use PHPUnit\Framework\Attributes\DependsExternal;

class GandiProviderTest extends ApiTestCase
{
    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testOrderDomain()
    {
        $gandiToken = static::getContainer()->getParameter('gandi_pat_token');
        if (!$gandiToken) {
            $this->markTestSkipped('Missing Gandi PAT token');
        }

        // Create a GANDI Connector
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
        $response = $client->request('POST', '/api/connectors', ['json' => [
            'authData' => [
                'waiveRetractationPeriod' => true,
                'acceptConditions' => true,
                'ownerLegalAge' => true,
                'token' => $gandiToken,
            ],
            'provider' => 'gandi',
        ]]);
        $this->assertResponseStatusCodeSame(201);

        // Create a Watchlist with a single domain name
        WatchListUpdateProcessorTest::createUserAndWatchlist($client, ['/api/domains/example.com'], '/api/connectors/'.$response->toArray()['id']);

        $response = $client->request('GET', '/api/watchlists');
        $watchlistId = $response->toArray()['hydra:member'][0]['token'];

        // Set the domain as deleted
        $domain = self::getContainer()->get(DomainRepository::class)->findOneBy(['ldhName' => 'example.com']);
        $domain->setDeleted(true);

        // Trigger the Order Domain message
        $orderDomainHandler = self::getContainer()->get(OrderDomainHandler::class);
        $message = new OrderDomain($watchlistId, 'example.com');
        $orderDomainHandler($message);

        $this->assertResponseStatusCodeSame(200);
    }
}
