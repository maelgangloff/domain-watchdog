<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\DomainRefreshController;
use App\Repository\DomainRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
#[UniqueEntity('handle')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: [
                'groups' => [
                    'domain:list'
                ]
            ]
        ),
        new Get(
            uriTemplate: '/domains/{ldhName}', # Do not delete this line, otherwise Symfony interprets the TLD of the domain name as a return type
            normalizationContext: [
                'groups' => [
                    'domain:item',
                    'event:list',
                    'entity:list',
                    'domain-entity:entity',
                    'nameserver-entity:nameserver',
                    'nameserver-entity:entity'
                ]
            ]
        ),
        new Post(
            uriTemplate: '/domains/{ldhName}',
            status: 204,
            controller: DomainRefreshController::class,
            openapiContext: [
                'summary' => 'Request an update of domain name data',
                'description' => 'Triggers a refresh of the domain information.',
                'requestBody' => false
            ],
            deserialize: false
        )
    ]
)]
class Domain
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item', 'watchlist:create'])]
    private ?string $ldhName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['domain:item', 'domain:list', 'watchlist:item'])]
    private ?string $handle = null;

    /**
     * @var Collection<int, DomainEvent>
     */
    #[ORM\OneToMany(targetEntity: DomainEvent::class, mappedBy: 'domain', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['domain:item'])]
    private Collection $events;

    /**
     * @var Collection<int, DomainEntity>
     */
    #[ORM\OneToMany(targetEntity: DomainEntity::class, mappedBy: 'domain', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['domain:item'])]
    private Collection $domainEntities;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    #[Groups(['domain:item'])]
    private array $status = [];

    /**
     * @var Collection<int, WatchList>
     */
    #[ORM\ManyToMany(targetEntity: WatchList::class, mappedBy: 'domains', cascade: ['persist'])]
    private Collection $watchLists;

    /**
     * @var Collection<int, Nameserver>
     */
    #[ORM\ManyToMany(targetEntity: Nameserver::class, inversedBy: 'domains', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'domain_nameservers',
        joinColumns: [new ORM\JoinColumn(name: 'domain_ldh_name', referencedColumnName: 'ldh_name')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'nameserver_ldh_name', referencedColumnName: 'ldh_name')]
    )]
    #[Groups(['domain:item'])]
    private Collection $nameservers;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'tld', nullable: false)]
    private ?Tld $tld = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->domainEntities = new ArrayCollection();
        $this->watchLists = new ArrayCollection();
        $this->nameservers = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable('now');
        $this->updatedAt = new DateTimeImmutable('now');
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
     * @return Collection<int, WatchList>
     */
    public function getWatchLists(): Collection
    {
        return $this->watchLists;
    }

    public function addWatchList(WatchList $watchList): static
    {
        if (!$this->watchLists->contains($watchList)) {
            $this->watchLists->add($watchList);
            $watchList->addDomain($this);
        }

        return $this;
    }

    public function removeWatchList(WatchList $watchList): static
    {
        if ($this->watchLists->removeElement($watchList)) {
            $watchList->removeDomain($this);
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

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->setUpdatedAt(new DateTimeImmutable('now'));
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new DateTimeImmutable('now'));
        }
    }

    private function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;

    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function setCreatedAt(?DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;

    }

    public function getTld(): ?Tld
    {
        return $this->tld;
    }

    public function setTld(?Tld $tld): static
    {
        $this->tld = $tld;

        return $this;
    }
}
