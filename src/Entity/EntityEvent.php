<?php

namespace App\Entity;

use App\Repository\EntityEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntityEventRepository::class)]
class EntityEvent extends Event
{

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Entity::class, inversedBy: 'events')]
    #[ORM\JoinColumn(referencedColumnName: 'handle', nullable: false)]
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
