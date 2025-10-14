<?php

namespace App\Tests\Controller;

use App\Entity\Connector;
use App\Factory\UserFactory;
use App\Tests\AbstractTest;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ConnectorControllerTest extends AbstractTest
{
    use ResetDatabase;
    use Factories;

    public function testGetConnectorCollection(): void
    {
        $testUser = UserFactory::createOne();
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken($testUser));

        $response = $client->request('GET', '/api/connectors');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(Connector::class);
        $this->assertCount(0, $response->toArray()['hydra:member']);
    }
}
