<?php

namespace App\Entity;

use App\Config\DomainStatus;
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
    private ?string $ldhName = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $handle = null;

    /**
     * @var Collection<int, DomainEvent>
     */
    #[ORM\OneToMany(targetEntity: DomainEvent::class, mappedBy: 'domain', orphanRemoval: true)]
    private Collection $events;

    /**
     * @var Collection<int, DomainEntity>
     */
    #[ORM\OneToMany(targetEntity: DomainEntity::class, mappedBy: 'domain', orphanRemoval: true)]
    private Collection $domainEntities;

    #[ORM\Column(length: 255)]
    private ?string $whoisStatus = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: DomainStatus::class)]
    private array $status = [];

    /**
     * @var Collection<int, BookmarkList>
     */
    #[ORM\ManyToMany(targetEntity: BookmarkList::class, mappedBy: 'domains')]
    private Collection $bookmarkLists;

    /**
     * @var Collection<int, Nameserver>
     */
    #[ORM\ManyToMany(targetEntity: Nameserver::class, inversedBy: 'domains')]
    #[ORM\JoinTable(name: 'domain_nameservers',
    joinColumns: [new ORM\JoinColumn(name: 'domain_handle', referencedColumnName: 'handle'), new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name')],
    inverseJoinColumns: [new ORM\JoinColumn(name: 'nameserver_handle', referencedColumnName: 'handle')]
    )]
    private Collection $nameservers;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->domainEntities = new ArrayCollection();
        $this->bookmarkLists = new ArrayCollection();
        $this->nameservers = new ArrayCollection();
    }

    public function getLdhName(): ?string
    {
        return $this->ldhName;
    }

    public function setLdhName(string $ldhName): static
    {
        $this->ldhName = $ldhName;

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
     * @return Collection<int, DomainEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(DomainEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setDomain($this);
        }

        return $this;
    }

    public function removeEvent(DomainEvent $event): static
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

    /**
     * @return DomainStatus[]
     */
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
     * @return Collection<int, BookmarkList>
     */
    public function getBookmarkLists(): Collection
    {
        return $this->bookmarkLists;
    }

    public function addBookmarkList(BookmarkList $bookmarkList): static
    {
        if (!$this->bookmarkLists->contains($bookmarkList)) {
            $this->bookmarkLists->add($bookmarkList);
            $bookmarkList->addDomain($this);
        }

        return $this;
    }

    public function removeBookmarkList(BookmarkList $bookmarkList): static
    {
        if ($this->bookmarkLists->removeElement($bookmarkList)) {
            $bookmarkList->removeDomain($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Nameserver>
     */
    public function getNameservers(): Collection
    {
        return $this->nameservers;
    }

    public function addNameserver(Nameserver $nameserver): static
    {
        if (!$this->nameservers->contains($nameserver)) {
            $this->nameservers->add($nameserver);
        }

        return $this;
    }

    public function removeNameserver(Nameserver $nameserver): static
    {
        $this->nameservers->removeElement($nameserver);

        return $this;
    }
}
