<?php

namespace App\Scheduler;

use App\Message\SendNotifWatchListTrigger;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('notif_watchlist')]
final readonly class SendNotifWatchListTriggerSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('1 week', new SendNotifWatchListTrigger()),
            )
            ->stateful($this->cache)
        ;
    }
}
