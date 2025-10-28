<?php

namespace App\Tests\MessageHandler;

use App\Message\ProcessAllWatchlist;
use App\Message\ProcessWatchlist;
use App\MessageHandler\ProcessAllWatchlistHandler;
use App\Tests\State\WatchlistUpdateProcessorTest;
use PHPUnit\Framework\Attributes\DependsExternal;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\TraceableMessageBus;

final class ProcessWatchlistTriggerHandlerTest extends KernelTestCase
{
    #[DependsExternal(WatchlistUpdateProcessorTest::class, 'testCreateWatchlist')]
    public function testSendMessage()
    {
        $container = self::getContainer();
        $handler = $container->get(ProcessAllWatchlistHandler::class);

        /** @var TraceableMessageBus $bus */
        $bus = $container->get('messenger.bus.default');
        $bus->reset();

        $handler(new ProcessAllWatchlist());

        $dispatched = $bus->getDispatchedMessages();
        $this->assertNotEmpty($dispatched);
        $this->assertInstanceOf(ProcessWatchlist::class, $dispatched[0]['message']);
    }
}
