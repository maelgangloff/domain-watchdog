<?php

namespace App\Scheduler;

use App\Message\ProcessWatchlistTrigger;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('notif_watchlist')]
final readonly class SendNotifWatchlistTriggerSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('5 minutes', new ProcessWatchlistTrigger()),
            )
            ->stateful($this->cache);
    }
}
