<?php

namespace App\Entity;

use App\Repository\DomainEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

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

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }
}
