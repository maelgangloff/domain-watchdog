<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Connector;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use Zenstruck\Foundry\Test\Factories;

final class ConnectorControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    public function testGetConnectorCollection(): void
    {
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));

        $response = $client->request('GET', '/api/connectors');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(Connector::class);
        $this->assertCount(0, $response->toArray()['hydra:member']);
    }

    public function testCreateConnectorInvalidAuthData(): void
    {
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
        $client->request('POST', '/api/connectors', ['json' => [
            'authData' => [
                'waiveRetractationPeriod' => true,
                'acceptConditions' => true,
                'ownerLegalAge' => true,
                'token' => '',
            ],
            'provider' => 'gandi',
        ]]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateConnectorInvalidConsent(): void
    {
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
        $client->request('POST', '/api/connectors', ['json' => [
            'authData' => [
                'waiveRetractationPeriod' => true,
                'acceptConditions' => true,
                'ownerLegalAge' => false,
                'token' => '',
            ],
            'provider' => 'gandi',
        ]]);
        $this->assertResponseStatusCodeSame(451);
    }

    public function testCreateConnectorInvalidAuthDataAdditionalKey(): void
    {
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
        $client->request('POST', '/api/connectors', ['json' => [
            'authData' => [
                'waiveRetractationPeriod' => true,
                'acceptConditions' => true,
                'ownerLegalAge' => true,
                'token' => '',
                'unknownKey' => 'hello',
            ],
            'provider' => 'gandi',
        ]]);
        $this->assertResponseStatusCodeSame(400);
    }
}
