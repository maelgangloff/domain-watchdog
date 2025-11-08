<?php

namespace App\Entity\RdapResponse;

class Event
{
    public function __construct(
        public string $eventDate,
        public string $eventAction,
    ) {
    }

    public static function fromEvent(\App\Entity\Event $event): self
    {
        return new Event($event->getDate()->format(\DateTimeInterface::ATOM), $event->getAction());
    }
}
