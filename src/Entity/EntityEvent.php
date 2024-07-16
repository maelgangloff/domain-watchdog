<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Repository\EntityEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntityEventRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/events/entity/{id}',
            shortName: 'Entity Event',
            class: EntityEvent::class,
            normalizationContext: [
                'groups' => ['event:list']
            ]
        )
    ]
)]
class EntityEvent extends Event
{

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
