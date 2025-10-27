<?php

namespace App\Tests\MessageHandler;

use App\Entity\Domain;
use App\Entity\Watchlist;
use App\Message\UpdateDomainsFromWatchlist;
use App\MessageHandler\UpdateDomainsFromWatchlistHandler;
use App\Tests\State\WatchlistUpdateProcessorTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsExternal;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\TraceableMessageBus;
use Symfony\Component\Uid\UuidV4;

final class UpdateDomainsFromWatchlistHandlerTest extends KernelTestCase
{
    #[DependsExternal(WatchlistUpdateProcessorTest::class, 'testCreateWatchlist')]
    public function testMessage()
    {
        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $handler = $container->get(UpdateDomainsFromWatchlistHandler::class);
        $bus = $container->get('messenger.bus.default');

        $deletedDomainLdhName = new UuidV4().'.com';

        $client = WatchlistUpdateProcessorTest::createUserAndWatchlist(null, ['/api/domains/'.$deletedDomainLdhName]);

        /** @var Domain $domain */
        $domain = $entityManager->getRepository(Domain::class)->findOneBy(['ldhName' => $deletedDomainLdhName]);
        $domain->setUpdatedAt((new \DateTimeImmutable())->setTimestamp(0));
        $entityManager->flush();

        $response = $client->request('GET', '/api/watchlists');

        /** @var Watchlist $watchlist */
        $watchlist = $entityManager->getRepository(Watchlist::class)->findOneBy(['token' => $response->toArray()['hydra:member'][0]['token']]);

        /* @var TraceableMessageBus $bus */
        $bus->reset();

        $handler(new UpdateDomainsFromWatchlist($watchlist->getToken()));

        $this->expectNotToPerformAssertions();
    }
}
