<?php

namespace App\Entity;

use App\Repository\EntityEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntityEventRepository::class)]
#[ORM\UniqueConstraint(
    columns: ['action', 'date', 'entity_uid']
)]
class EntityEvent extends Event
{
    #[ORM\ManyToOne(targetEntity: Entity::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'entity_uid', referencedColumnName: 'id', nullable: false)]
    private ?Entity $entity = null;

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function setEntity(?Entity $entity): static
    {
        $this->entity = $entity;

        return $this;
    }
}
