<?php

namespace App\Entity\RdapResponse;

use App\Config\EventAction;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;

class Domain
{
    public string $objectClassName = 'domain';
    public ?string $handle;
    public string $ldhName;
    /**
     * @var string[]
     */
    public ?array $status;
    /**
     * @var Event[]
     */
    public ?array $events;

    /**
     * @var Entity[]
     */
    public ?array $entities;
    /**
     * @var Nameserver[]
     */
    public ?array $nameservers;

    public ?SecureDNS $secureDNS;

    /**
     * @throws \Exception
     */
    public function __construct(\App\Entity\Domain $d)
    {
        $this->handle = $d->getHandle();
        $this->ldhName = $d->getLdhName();
        $this->status = $d->getStatus();
        $events = $d->getEvents()->toArray();
        usort($events, fn (DomainEvent $d1, DomainEvent $d2) => $d1->getDate()->getTimestamp() - $d2->getDate()->getTimestamp());
        $this->events = [
            ...array_map(fn (DomainEvent $de) => Event::fromEvent($de), $events),
            new Event($d->getUpdatedAt()->format(\DateTimeInterface::ATOM), EventAction::LastUpdateOfRDAPDatabase->value),
        ];
        $this->entities = array_map(fn (DomainEntity $de) => Entity::fromDomainEntity($de), $d->getDomainEntities()->toArray());
        $this->nameservers = array_map(fn (\App\Entity\Nameserver $ns) => Nameserver::fromNameserver($ns), $d->getNameservers()->toArray());
        $this->secureDNS = SecureDNS::fromDomain($d);
    }
}
