<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Repository\DomainEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEventRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/events/domain/{id}',
            shortName: 'Domain Event',
            class: DomainEvent::class,
            normalizationContext: ['groups' => ['event:list']]
        )
    ]
)]
class DomainEvent extends Event
{
    #[ORM\ManyToOne(targetEntity: Domain::class, cascade: ['persist'], inversedBy: 'events')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    private ?Domain $domain = null;

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }
}
