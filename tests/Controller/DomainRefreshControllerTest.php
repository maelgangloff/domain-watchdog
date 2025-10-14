<?php

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Domain;
use App\Factory\UserFactory;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class DomainRefreshControllerTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testRegisterDomain(): void
    {
        $testUser = UserFactory::createOne();
        $client = DomainRefreshControllerTest::createClientWithCredentials(DomainRefreshControllerTest::getToken($testUser));
        $client->request('GET', '/api/domains/example.com');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Domain::class);
    }
}
