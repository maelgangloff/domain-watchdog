<?php

namespace App\Tests\MessageHandler;

use App\Message\ProcessWatchlistTrigger;
use App\Message\UpdateDomainsFromWatchlist;
use App\MessageHandler\ProcessWatchlistTriggerHandler;
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
        $handler = $container->get(ProcessWatchlistTriggerHandler::class);

        /** @var TraceableMessageBus $bus */
        $bus = $container->get('messenger.bus.default');
        $bus->reset();

        $handler(new ProcessWatchlistTrigger());

        $dispatched = $bus->getDispatchedMessages();
        $this->assertNotEmpty($dispatched);
        $this->assertInstanceOf(UpdateDomainsFromWatchlist::class, $dispatched[0]['message']);
    }
}
