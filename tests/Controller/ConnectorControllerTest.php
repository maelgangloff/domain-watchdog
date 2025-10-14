<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Connector;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ConnectorControllerTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;
    use AuthenticatedUserTrait;

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
