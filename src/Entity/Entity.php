<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: EntityRepository::class)]
#[ORM\UniqueConstraint(
    columns: ['tld_id', 'handle']
)]
#[ApiResource(
    operations: [
        /*
        new GetCollection(
            uriTemplate: '/entities',
            normalizationContext: ['groups' => ['entity:list', 'event:list']]
        ),

        new Get(
            uriTemplate: '/entities/{id}',
            normalizationContext: [
                'groups' => [
                    'event:list',
                    'entity:item',
                    'domain-entity:domain',
                    'domain:list',
                    'nameserver-entity:nameserver',
                    'nameserver:list',
                ],
            ]
        ),
        */
    ]
)]
class Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tld::class, inversedBy: 'entities')]
    #[ORM\JoinColumn(referencedColumnName: 'tld', nullable: true)]
    #[Groups(['entity:list', 'entity:item', 'domain:item'])]
    private ?Tld $tld = null;

    #[ORM\Column(length: 255)]
    #[Groups(['entity:list', 'entity:item', 'domain:item'])]
    private ?string $handle = null;

    /**
     * @var Collection<int, DomainEntity>
     */
    #[ORM\OneToMany(targetEntity: DomainEntity::class, mappedBy: 'entity', orphanRemoval: true)]
    #[Groups(['entity:item'])]
    #[SerializedName('domains')]
    private Collection $domainEntities;

    /**
     * @var Collection<int, NameserverEntity>
     */
    #[ORM\OneToMany(targetEntity: NameserverEntity::class, mappedBy: 'entity')]
    #[Groups(['entity:item'])]
    #[SerializedName('nameservers')]
    private Collection $nameserverEntities;

    /**
     * @var Collection<int, EntityEvent>
     */
    #[ORM\OneToMany(targetEntity: EntityEvent::class, mappedBy: 'entity', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['entity:list', 'entity:item', 'entity:list', 'domain:item'])]
    private Collection $events;

    #[ORM\Column]
    #[Groups(['entity:item', 'domain:item'])]
    private array $jCard = [];

    #[ORM\Column(nullable: true)]
    #[Groups(['entity:item', 'domain:item'])]
    private ?array $remarks = null;

    #[Embedded(class: IanaAccreditation::class, columnPrefix: 'iana_')]
    private ?IanaAccreditation $ianaAccreditation;

    public function __construct()
    {
        $this->domainEntities = new ArrayCollection();
        $this->nameserverEntities = new ArrayCollection();
        $this->events = new ArrayCollection();
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
            $domainEntity->setEntity($this);
        }

        return $this;
    }

    public function removeDomainEntity(DomainEntity $domainEntity): static
    {
        if ($this->domainEntities->removeElement($domainEntity)) {
            // set the owning side to null (unless already changed)
            if ($domainEntity->getEntity() === $this) {
                $domainEntity->setEntity(null);
            }
        }

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
            $nameserverEntity->setEntity($this);
        }

        return $this;
    }

    public function removeNameserverEntity(NameserverEntity $nameserverEntity): static
    {
        if ($this->nameserverEntities->removeElement($nameserverEntity)) {
            // set the owning side to null (unless already changed)
            if ($nameserverEntity->getEntity() === $this) {
                $nameserverEntity->setEntity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EntityEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(EntityEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setEntity($this);
        }

        return $this;
    }

    public function removeEvent(EntityEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getEntity() === $this) {
                $event->setEntity(null);
            }
        }

        return $this;
    }

    public function getJCard(): array
    {
        return $this->jCard;
    }

    public function setJCard(array $jCard): static
    {
        $this->jCard = $jCard;

        return $this;
    }

    public function getRemarks(): ?array
    {
        return $this->remarks;
    }

    public function setRemarks(?array $remarks): static
    {
        $this->remarks = $remarks;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getIanaAccreditation(): ?IanaAccreditation
    {
        return $this->ianaAccreditation;
    }

    public function setIanaAccreditation(?IanaAccreditation $ianaAccreditation): void
    {
        $this->ianaAccreditation = $ianaAccreditation;
    }
}
