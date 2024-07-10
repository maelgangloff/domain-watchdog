<?php

namespace App\Entity;

use App\Repository\NameserverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameserverRepository::class)]
class Nameserver
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $handle = null;

    #[ORM\Column(length: 255)]
    private ?string $ldhname = null;

    /**
     * @var Collection<int, NameserverEntity>
     */
    #[ORM\OneToMany(targetEntity: NameserverEntity::class, mappedBy: 'nameserver', orphanRemoval: true)]
    private Collection $nameserverEntities;

    #[ORM\Column(type: Types::ARRAY)]
    private array $status = [];

    public function __construct()
    {
        $this->nameserverEntities = new ArrayCollection();
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): static
    {
        $this->handle = $handle;

        return $this;
    }

    public function getLdhname(): ?string
    {
        return $this->ldhname;
    }

    public function setLdhname(string $ldhname): static
    {
        $this->ldhname = $ldhname;

        return $this;
    }

    /**
     * @return Collection<int, NameserverEntity>
     */
    public function getNameserverEntities(): Collection
    {
        return $this->nameserverEntities;
    }

    public function addNameserverEntity(NameserverEntity $nameserverEntity): static
    {
        if (!$this->nameserverEntities->contains($nameserverEntity)) {
            $this->nameserverEntities->add($nameserverEntity);
            $nameserverEntity->setNameserver($this);
        }

        return $this;
    }

    public function removeNameserverEntity(NameserverEntity $nameserverEntity): static
    {
        if ($this->nameserverEntities->removeElement($nameserverEntity)) {
            // set the owning side to null (unless already changed)
            if ($nameserverEntity->getNameserver() === $this) {
                $nameserverEntity->setNameserver(null);
            }
        }

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
