<?php

namespace App\Tests\Service;

use App\Entity\RdapServer;
use App\Exception\DomainNotFoundException;
use App\Exception\TldNotSupportedException;
use App\Exception\UnknownRdapServerException;
use App\Message\UpdateRdapServers;
use App\MessageHandler\UpdateRdapServersHandler;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;

class RDAPServiceTest extends KernelTestCase
{
    protected static ContainerInterface $container;
    protected static EntityManagerInterface $entityManager;
    protected static RDAPService $RDAPService;

    protected function setUp(): void
    {
        static::$container = static::getContainer();
        static::$entityManager = static::$container->get(EntityManagerInterface::class);
        static::$RDAPService = static::$container->get(RDAPService::class);
    }

    public function testUpdateRdapServers(): void
    {
        $updateRdapServersHandler = self::$container->get(UpdateRdapServersHandler::class);
        $message = new UpdateRdapServers();
        $updateRdapServersHandler($message);
        self::$entityManager->flush();

        $rdapServerRepository = self::$entityManager->getRepository(RdapServer::class);
        $this->assertNotEmpty($rdapServerRepository->findAll());
    }

    #[Depends('testUpdateRdapServers')]
    public function testRegisterDomain(): void
    {
        $rdapServerRepository = self::$entityManager->getRepository(RdapServer::class);

        $testedTldList = ['com', 'net', 'org', 'fr', 'de', 'ch', 'ca', 'leclerc', 'uz'];

        /** @var RdapServer $rdapServer */
        foreach ($rdapServerRepository->findAll() as $rdapServer) {
            if (!in_array($rdapServer->getTld()->getTld(), $testedTldList)) {
                continue;
            }
            try {
                self::$RDAPService->registerDomain('nic.'.$rdapServer->getTld()->getTld());
            } catch (DomainNotFoundException|ClientException|TransportException) {
            }
        }
        $this->expectNotToPerformAssertions();
    }

    #[Depends('testUpdateRdapServers')]
    public function testUnknownRdapServer(): void
    {
        $this->expectException(UnknownRdapServerException::class);
        self::$RDAPService->registerDomain('nic.arpa');
    }

    #[Depends('testUpdateRdapServers')]
    public function testUnknownTld(): void
    {
        $this->expectException(TldNotSupportedException::class);
        self::$RDAPService->registerDomain('nic.noexist');
    }
}
