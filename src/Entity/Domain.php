<?php

namespace App\Entity;

use App\Repository\DomainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
class Domain
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $ldhname = null;

    #[ORM\Column(length: 255)]
    private ?string $handle = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $status = [];

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'domain', orphanRemoval: true)]
    private Collection $events;

    /**
     * @var Collection<int, DomainEntity>
     */
    #[ORM\OneToMany(targetEntity: DomainEntity::class, mappedBy: 'domain', orphanRemoval: true)]
    private Collection $domainEntities;

    #[ORM\Column(length: 255)]
    private ?string $whoisStatus = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->domainEntities = new ArrayCollection();
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

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): static
    {
        $this->handle = $handle;

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

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setDomain($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getDomain() === $this) {
                $event->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DomainEntity>
     */
    public function getDomainEntities(): Collection
    {
        return $this->domainEntities;
    }

    public function addDomainEntity(DomainEntity $domainEntity): static
    {
        if (!$this->domainEntities->contains($domainEntity)) {
            $this->domainEntities->add($domainEntity);
            $domainEntity->setDomain($this);
        }

        return $this;
    }

    public function removeDomainEntity(DomainEntity $domainEntity): static
    {
        if ($this->domainEntities->removeElement($domainEntity)) {
            // set the owning side to null (unless already changed)
            if ($domainEntity->getDomain() === $this) {
                $domainEntity->setDomain(null);
            }
        }

        return $this;
    }

    public function getWhoisStatus(): ?string
    {
        return $this->whoisStatus;
    }

    public function setWhoisStatus(string $whoisStatus): static
    {
        $this->whoisStatus = $whoisStatus;

        return $this;
    }
}
