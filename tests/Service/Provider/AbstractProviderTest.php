<?php

namespace App\Tests\Service\Provider;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Config\ConnectorProvider;
use App\Entity\Domain;
use App\Entity\Tld;
use App\Entity\WatchList;
use App\Factory\UserFactory;
use App\Message\OrderDomain;
use App\MessageHandler\OrderDomainHandler;
use App\Tests\Controller\ConnectorControllerTest;
use App\Tests\Service\RDAPServiceTest;
use App\Tests\State\WatchListUpdateProcessorTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsExternal;
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

    private function testGenericProvider(ConnectorProvider $connectorProvider, array $authData): void
    {
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
        WatchListUpdateProcessorTest::createUserAndWatchlist($client,
            ['/api/domains/example.com'],
            '/api/connectors/'.$response->toArray()['id']);

        $response = $client->request('GET', '/api/watchlists');
        $watchlist = $entityManager->getRepository(WatchList::class)->findOneBy(['token' => $response->toArray()['hydra:member'][0]['token']]);

        $domain = (new Domain())
            ->setLdhName((new UuidV4()).'.com')
            ->setDeleted(true)
            ->setTld($entityManager->getReference(Tld::class, 'fr'))
            ->setDelegationSigned(false);

        $entityManager->persist($domain);
        $watchlist->addDomain($domain);

        $entityManager->flush();

        // Trigger the Order Domain message
        $orderDomainHandler = self::getContainer()->get(OrderDomainHandler::class);
        $message = new OrderDomain($watchlist->getToken(), $domain->getLdhName());
        $orderDomainHandler($message);

        $this->assertResponseStatusCodeSame(200);
    }
}
