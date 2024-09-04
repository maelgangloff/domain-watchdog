<?php

namespace App\Entity;

use App\Config\DomainRole;
use App\Repository\DomainEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DomainEntityRepository::class)]
class DomainEntity
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Domain::class, cascade: ['persist'], inversedBy: 'domainEntities')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    #[Groups('domain-entity:domain')]
    private ?Domain $domain = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Entity::class, cascade: ['persist'], inversedBy: 'domainEntities')]
    #[ORM\JoinColumn(referencedColumnName: 'handle', nullable: false)]
    #[Groups(['domain-entity:entity'])]
    private ?Entity $entity = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    #[Groups(['domain-entity:entity', 'domain-entity:domain'])]
    private array $roles = [];

    #[ORM\Column]
    #[Groups(['domain-entity:entity', 'domain-entity:domain'])]
    private ?bool $deleted;

    public function __construct()
    {
        $this->deleted = false;
    }

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

    /**
     * @return DomainRole[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
