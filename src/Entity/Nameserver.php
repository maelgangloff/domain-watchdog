<?php

namespace App\Entity;

use App\Repository\NameserverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameserverRepository::class)]
class Nameserver
{

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $ldhName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $handle = null;

    /**
     * @var Collection<int, NameserverEntity>
     */
    #[ORM\OneToMany(targetEntity: NameserverEntity::class, mappedBy: 'nameserver', cascade: ['persist'], orphanRemoval: true)]
    private Collection $nameserverEntities;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\ManyToMany(targetEntity: Domain::class, mappedBy: 'nameservers')]
    private Collection $domains;

    public function __construct()
    {
        $this->nameserverEntities = new ArrayCollection();
        $this->domains = new ArrayCollection();
    }

    public function getLdhName(): ?string
    {
        return $this->ldhName;
    }

    public function setLdhName(string $ldhName): static
    {
        $this->ldhName = strtolower($ldhName);

        return $this;
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

    /**
     * @return Collection<int, Domain>
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): static
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
            $domain->addNameserver($this);
        }

        return $this;
    }

    public function removeDomain(Domain $domain): static
    {
        if ($this->domains->removeElement($domain)) {
            $domain->removeNameserver($this);
        }

        return $this;
    }

}
