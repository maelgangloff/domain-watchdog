<?php

namespace App\Entity;

use App\Repository\NameserverEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameserverEntityRepository::class)]
class NameserverEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nameserverEntities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Nameserver $nameserver = null;

    #[ORM\ManyToOne(inversedBy: 'nameserverEntities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entity $entity = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

    #[ORM\Column(type: Types::ARRAY)]
    private array $status = [];

    public function getId(): ?int
    {
        return $this->id;
    }

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
