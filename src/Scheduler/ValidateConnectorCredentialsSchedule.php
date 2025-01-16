<?php

namespace App\Scheduler;

use App\Message\ValidateConnectorCredentials;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('ValidateConnectorCredentials')]
final class ValidateConnectorCredentialsSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('1 week', new ValidateConnectorCredentials()),
            )
            ->stateful($this->cache)
        ;
    }
}
