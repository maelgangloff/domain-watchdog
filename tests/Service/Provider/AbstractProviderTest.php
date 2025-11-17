<?php

namespace App\Tests\Service\Provider;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Config\ConnectorProvider;
use App\Entity\Domain;
use App\Entity\Tld;
use App\Entity\Watchlist;
use App\Factory\UserFactory;
use App\Message\OrderDomain;
use App\MessageHandler\OrderDomainHandler;
use App\Tests\Controller\ConnectorControllerTest;
use App\Tests\Service\RDAPServiceTest;
use App\Tests\State\WatchlistUpdateProcessorTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsExternal;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\Uid\UuidV4;

class AbstractProviderTest extends ApiTestCase
{
    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testGandi()
    {
        $gandiToken = static::getContainer()->getParameter('gandi_pat_token');
        if (!$gandiToken) {
            $this->markTestSkipped('Missing Gandi PAT token');
        }

        $this->testGenericProvider(ConnectorProvider::GANDI, [
            'waiveRetractationPeriod' => true,
            'acceptConditions' => true,
            'ownerLegalAge' => true,
            'token' => $gandiToken,
        ]);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testNameCom()
    {
        $namecomUsername = static::getContainer()->getParameter('namecom_username');
        $namecomPassword = static::getContainer()->getParameter('namecom_password');

        if (!$namecomUsername || !$namecomPassword) {
            $this->markTestSkipped('Missing Name.com username or password');
        }

        $this->testGenericProvider(ConnectorProvider::NAMECOM, [
            'waiveRetractationPeriod' => true,
            'acceptConditions' => true,
            'ownerLegalAge' => true,
            'username' => $namecomUsername,
            'token' => $namecomPassword,
        ]);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testNamecheap()
    {
        $namecheapUsername = static::getContainer()->getParameter('namecheap_username');
        $namecheapToken = static::getContainer()->getParameter('namecheap_token');

        if (!$namecheapUsername || !$namecheapToken) {
            $this->markTestSkipped('Missing Namecheap username or token');
        }

        $this->testGenericProvider(ConnectorProvider::NAMECHEAP, [
            'waiveRetractationPeriod' => true,
            'acceptConditions' => true,
            'ownerLegalAge' => true,
            'ApiUser' => $namecheapUsername,
            'ApiKey' => $namecheapToken,
        ]);
    }

    #[DependsExternal(RDAPServiceTest::class, 'testUpdateRdapServers')]
    public function testOpenProvider()
    {
        $openproviderToken = static::getContainer()->getParameter('openprovider_token');

        if (!$openproviderToken) {
            $this->markTestSkipped('Missing OpenProvider token');
        }

        $this->testGenericProvider(ConnectorProvider::OPENPROVIDER, [
            'waiveRetractationPeriod' => true,
            'acceptConditions' => true,
            'ownerLegalAge' => true,
            'token' => $openproviderToken,
            'adminHandle' => 'HANDLE',
            'billingHandle' => 'HANDLE',
            'ownerHandle' => 'HANDLE',
            'techHandle' => 'HANDLE',
            'period' => 1,
            'nsGroup' => 'dns-openprovider',
        ]);
    }

    private function testGenericProvider(ConnectorProvider $connectorProvider, array $authData): void
    {
        try {
            // Create a Connector
            $client = ConnectorControllerTest::createClientWithCredentials(ConnectorControllerTest::getToken(UserFactory::createOne()));
            $response = $client->request('POST', '/api/connectors', ['json' => [
                'authData' => $authData,
                'provider' => $connectorProvider->value,
            ]]);
            $this->assertResponseStatusCodeSame(201);

            /** @var EntityManagerInterface $entityManager */
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);

            // Create a Watchlist with the domain name
            WatchlistUpdateProcessorTest::createUserAndWatchlist($client,
                ['/api/domains/example.com'],
                '/api/connectors/'.$response->toArray()['id']);

            $response = $client->request('GET', '/api/watchlists');
            $watchlist = $entityManager->getRepository(Watchlist::class)->findOneBy(['token' => $response->toArray()['hydra:member'][0]['token']]);

            $domain = (new Domain())
                ->setLdhName((new UuidV4()).'.com')
                ->setDeleted(true)
                ->setTld($entityManager->getReference(Tld::class, 'com'))
                ->setDelegationSigned(false);

            $entityManager->persist($domain);
            $watchlist->addDomain($domain);

            $entityManager->flush();

            // Trigger the Order Domain message
            $orderDomainHandler = self::getContainer()->get(OrderDomainHandler::class);
            $message = new OrderDomain($watchlist->getToken(), $domain->getLdhName(), $domain->getUpdatedAt());
            $orderDomainHandler($message);

            $this->assertResponseStatusCodeSame(200);
        } catch (ServerException $e) {
            $this->markTestSkipped('Provider '.$connectorProvider->value.' is not ready. Response HTTP '.$e->getResponse()->getStatusCode());
        }
    }
}
