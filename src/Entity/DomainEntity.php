<?php

namespace App\Entity;

use App\Repository\DomainEntityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEntityRepository::class)]
class DomainEntity
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'domainEntities')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'ldhname')]
    private ?Domain $domain = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'domainEntities')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'handle')]
    private ?Entity $entity = null;

    #[ORM\Column(length: 255)]
    private ?string $roles = null;

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function setEntity(?Entity $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $roles;

        return $this;
    }
}
