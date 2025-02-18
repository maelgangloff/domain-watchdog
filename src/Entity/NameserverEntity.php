<?php

namespace App\Entity;

use App\Config\DomainRole;
use App\Repository\NameserverEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: NameserverEntityRepository::class)]
class NameserverEntity
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Nameserver::class, cascade: ['persist'], inversedBy: 'nameserverEntities')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    #[Groups(['nameserver-entity:nameserver'])]
    private ?Nameserver $nameserver = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Entity::class, cascade: ['persist'], inversedBy: 'nameserverEntities')]
    #[ORM\JoinColumn(name: 'entity_uid', referencedColumnName: 'id', nullable: false)]
    #[Groups(['nameserver-entity:entity'])]
    private ?Entity $entity = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    #[Groups(['nameserver-entity:entity', 'nameserver-entity:nameserver'])]
    private array $roles = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    #[Groups(['nameserver-entity:entity', 'nameserver-entity:nameserver'])]
    private array $status = [];

    public function getNameserver(): ?Nameserver
    {
        return $this->nameserver;
    }

    public function setNameserver(?Nameserver $nameserver): static
    {
        $this->nameserver = $nameserver;

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

    public function getStatus(): array
    {
        return $this->status;
    }

    public function setStatus(array $status): static
    {
        $this->status = $status;

        return $this;
    }
}
