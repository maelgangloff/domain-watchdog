<?php

namespace App\Tests\Service;

use App\Entity\RdapServer;
use App\Entity\Tld;
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
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

        $rdapServerRepository = self::$entityManager->getRepository(RdapServer::class);
        $this->assertNotEmpty($rdapServerRepository->findAll());
    }

    #[Depends('testUpdateRdapServers')]
    public function testRegisterDomainByTld(): void
    {
        self::$RDAPService->registerDomains(['arpa']);

        $rdapServerRepository = static::$entityManager->getRepository(RdapServer::class);
        $rdapServerList = $rdapServerRepository->findBy(['tld' => array_map(
            fn (string $tld) => static::$entityManager->getReference(Tld::class, $tld),
            ['com', 'net', 'fr', 'de', 'ch', 'ca', 'uz', 'google', 'ovh']
        ),
        ]);

        foreach ($rdapServerList as $rdapServer) {
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

    #[Depends('testUpdateRdapServers')]
    public function testDomainDeleted()
    {
        self::$RDAPService->registerDomain('example.com');

        self::ensureKernelShutdown();
        self::bootKernel();

        static::getContainer()->set(HttpClientInterface::class, new MockHttpClient(
            new MockResponse('', ['http_code' => 404])
        ));

        $rdapService = static::getContainer()->get(RDAPService::class);

        $this->expectException(DomainNotFoundException::class);
        $rdapService->registerDomain('example.com');
    }

    #[Depends('testDomainDeleted')]
    public function testDomainUpdateStatus(): void
    {
        $domain = self::$RDAPService->registerDomain('example.com');
        $domain->setStatus(['pending delete']);
        self::$entityManager->flush();
        self::$RDAPService->registerDomain('example.com');

        $this->expectNotToPerformAssertions();
    }

    #[Depends('testUpdateRdapServers')]
    public function testHttpClientException()
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        static::getContainer()->set(HttpClientInterface::class, new MockHttpClient(
            fn () => throw new TransportException()
        ));

        $rdapService = static::getContainer()->get(RDAPService::class);

        $this->expectException(TransportException::class);
        $rdapService->registerDomain('example.com');
    }
}
