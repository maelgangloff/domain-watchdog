<?php

namespace App\Entity;

use App\Repository\DomainEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEventRepository::class)]
#[ORM\UniqueConstraint(
    columns: ['action', 'date', 'domain_id']
)]
class DomainEvent extends Event
{
    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'events')]
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
