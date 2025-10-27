<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Connector;
use App\Factory\UserFactory;
use App\Message\ValidateConnectorCredentials;
use App\MessageHandler\ValidateConnectorCredentialsHandler;
use App\Tests\AuthenticatedUserTrait;
use PHPUnit\Framework\Attributes\Depends;
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
                'token' => 'test',
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

    public function testCreateConnectorValidAuthData(): void
    {
        $gandiToken = static::getContainer()->getParameter('gandi_pat_token');
        if (!$gandiToken) {
            $this->markTestSkipped('Missing Gandi PAT token');
        }
        $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
        $client->request('POST', '/api/connectors', ['json' => [
            'authData' => [
                'waiveRetractationPeriod' => true,
                'acceptConditions' => true,
                'ownerLegalAge' => true,
                'token' => $gandiToken,
            ],
            'provider' => 'gandi',
        ]]);
        $this->assertResponseStatusCodeSame(201);
    }

    #[Depends('testCreateConnectorValidAuthData')]
    public function testValidateConnectorCredentials()
    {
        $validateConnectorCredentialsHandler = self::getContainer()->get(ValidateConnectorCredentialsHandler::class);
        $message = new ValidateConnectorCredentials();
        $validateConnectorCredentialsHandler($message);

        $this->expectNotToPerformAssertions();
    }

    public function testCreateAndDeleteConnector()
    {
        $gandiToken = static::getContainer()->getParameter('gandi_pat_token');
        if (!$gandiToken) {
            $this->markTestSkipped('Missing Gandi PAT token');
        }
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

        $client->request('DELETE', '/api/connectors/'.$response->toArray()['id']);

        $this->assertResponseStatusCodeSame(204);
    }
}
