<?php

namespace App\Tests\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Domain;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class FindDomainCollectionFromEntityProviderTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testFilterDomainCollection(): void
    {
        $client = FindDomainCollectionFromEntityProviderTest::createClientWithCredentials(FindDomainCollectionFromEntityProviderTest::getToken(UserFactory::createOne()));
        $client->request('GET', '/api/domains/nic.fr')->toArray();

        $this->assertResponseIsSuccessful();
        $response = $client->request('GET', '/api/domains', [
            'query' => ['registrant' => 'AFNIC'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceCollectionJsonSchema(Domain::class);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);
    }
}
