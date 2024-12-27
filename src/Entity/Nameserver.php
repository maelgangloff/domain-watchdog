<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Repository\NameserverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: NameserverRepository::class)]
#[ApiResource(
    operations: [
        /*
        new GetCollection(
            uriTemplate: '/nameservers',
            normalizationContext: [
                'groups' => [
                    'nameserver:list',
                ],
            ]
        ),
        */
        new Get(
            uriTemplate: '/nameservers/{ldhName}',
            normalizationContext: [
                'groups' => [
                    'nameserver:item',
                    'nameserver-entity:entity',
                    'entity:list',
                    'event:list',
                ],
            ]
        ),
    ]
)]
class Nameserver
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['nameserver:item', 'nameserver:list', 'domain:item'])]
    private ?string $ldhName = null;

    /**
     * @var Collection<int, NameserverEntity>
     */
    #[ORM\OneToMany(targetEntity: NameserverEntity::class, mappedBy: 'nameserver', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['nameserver:item', 'domain:item'])]
    #[SerializedName('entities')]
    private Collection $nameserverEntities;

    /**
     * @var Collection<int, Domain>
     */
    #[ORM\ManyToMany(targetEntity: Domain::class, mappedBy: 'nameservers')]
    #[Groups(['nameserver:item'])]
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
