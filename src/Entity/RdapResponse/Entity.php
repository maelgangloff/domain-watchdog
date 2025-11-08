<?php

namespace App\Entity\RdapResponse;

use App\Config\DomainRole;
use App\Entity\DomainEntity;
use App\Entity\EntityEvent;

class Entity
{
    public string $objectClassName = 'entity';
    public ?string $handle;

    /**
     * @var PublicId[]
     */
    public ?array $publicIds;
    /**
     * @var Event[]
     */
    public ?array $events;
    /**
     * @var Link[]
     */
    public array $vcardArray;

    /**
     * @var DomainRole[]
     */
    public array $roles;

    public ?array $remarks;

    public function __construct(\App\Entity\Entity $e)
    {
        if (!str_starts_with($e->getHandle(), 'DW-FAKEHANDLE')) {
            $this->handle = $e->getHandle();
        }
        if ($e->getIcannAccreditation()) {
            $this->publicIds = [new PublicId('IANA Registrar ID', (string) $e->getIcannAccreditation()->getId())];
        }
        $this->vcardArray = $e->getJCard();
        $this->remarks = $e->getRemarks();
    }

    public static function fromDomainEntity(DomainEntity $de): self
    {
        $e = new Entity($de->getEntity());
        $e->events = array_map(fn (EntityEvent $ee) => Event::fromEvent($ee), $de->getEntity()->getEvents()->toArray());
        $e->roles = $de->getRoles();

        return $e;
    }
}
