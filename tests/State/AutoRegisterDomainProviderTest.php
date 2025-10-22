<?php

namespace App\Tests\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Domain;
use App\Factory\UserFactory;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use App\Tests\AuthenticatedUserTrait;
use App\Tests\Service\RDAPServiceTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Zenstruck\Foundry\Test\Factories;

final class AutoRegisterDomainProviderTest extends ApiTestCase
{
    use Factories;
    use AuthenticatedUserTrait;

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testRegisterDomain(): void
    {
        $client = AutoRegisterDomainProviderTest::createClientWithCredentials(AutoRegisterDomainProviderTest::getToken(UserFactory::createOne()));
        $client->request('GET', '/api/domains/example.com');

        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Domain::class);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testRegisterDomainAlreadyUpdated(): void
    {
        $client = AutoRegisterDomainProviderTest::createClientWithCredentials(AutoRegisterDomainProviderTest::getToken(UserFactory::createOne()));

        $mockedDomain = $this->getMockBuilder(Domain::class)->getMock();

        $mockedDomainRepository = $this->createMock(DomainRepository::class);
        $mockedDomainRepository->method('findOneBy')->willReturn($mockedDomain);

        $rdapServiceMocked = $this->createMock(RDAPService::class);
        $rdapServiceMocked->method('isToBeUpdated')->willReturn(false);
        $rdapServiceMocked->expects(self::never())->method('registerDomain');

        $container = static::getContainer();
        $container->set(DomainRepository::class, $mockedDomainRepository);
        $container->set(RDAPService::class, $rdapServiceMocked);

        $client->request('GET', '/api/domains/example.com');
    }
}
