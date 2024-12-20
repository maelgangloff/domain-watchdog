<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Config\TldType;
use App\Repository\TldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Top Level Domain',
    operations: [
        new GetCollection(
            uriTemplate: '/tld',
            normalizationContext: ['groups' => ['tld:list']]
        ),
        new Get(
            uriTemplate: '/tld/{tld}',
            normalizationContext: ['groups' => ['tld:item']]
        ),
    ]
)]
#[ApiFilter(SearchFilter::class)]
#[ORM\Entity(repositoryClass: TldRepository::class)]
class Tld
{
    #[ORM\Id]
    #[ORM\Column(length: 63)]
    #[Groups(['tld:list', 'tld:item'])]
    private ?string $tld = null;
    /**
     * @var Collection<int, RdapServer>
     */
    #[ORM\OneToMany(targetEntity: RdapServer::class, mappedBy: 'tld', orphanRemoval: true)]
    private Collection $rdapServers;

    #[ORM\Column(nullable: true)]
    #[Groups(['tld:list', 'tld:item'])]
    private ?bool $contractTerminated = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['tld:item'])]
    private ?\DateTimeImmutable $dateOfContractSignature = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['tld:item'])]
    private ?\DateTimeImmutable $delegationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tld:list', 'tld:item'])]
    private ?string $registryOperator = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['tld:item'])]
    private ?\DateTimeImmutable $removalDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['tld:list', 'tld:item'])]
    private ?bool $specification13 = null;

    #[ORM\Column(length: 10, enumType: TldType::class)]
    #[Groups(['tld:item'])]
    private ?TldType $type = null;

    public function __construct()
    {
        $this->rdapServers = new ArrayCollection();
    }

    /**
     * @return Collection<int, RdapServer>
     */
    public function getRdapServers(): Collection
    {
        return $this->rdapServers;
    }

    public function addRdapServer(RdapServer $rdapServer): static
    {
        if (!$this->rdapServers->contains($rdapServer)) {
            $this->rdapServers->add($rdapServer);
            $rdapServer->setTld($this);
        }

        return $this;
    }

    public function removeRdapServer(RdapServer $rdapServer): static
    {
        if ($this->rdapServers->removeElement($rdapServer)) {
            // set the owning side to null (unless already changed)
            if ($rdapServer->getTld() === $this) {
                $rdapServer->setTld(null);
            }
        }

        return $this;
    }

    public function getTld(): ?string
    {
        return '' === $this->tld ? '.' : $this->tld;
    }

    public function setTld(string $tld): static
    {
        $this->tld = strtolower($tld);

        return $this;
    }

    public function isContractTerminated(): ?bool
    {
        return $this->contractTerminated;
    }

    public function setContractTerminated(?bool $contractTerminated): static
    {
        $this->contractTerminated = $contractTerminated;

        return $this;
    }

    public function getDateOfContractSignature(): ?\DateTimeImmutable
    {
        return $this->dateOfContractSignature;
    }

    public function setDateOfContractSignature(?\DateTimeImmutable $dateOfContractSignature): static
    {
        $this->dateOfContractSignature = $dateOfContractSignature;

        return $this;
    }

    public function getDelegationDate(): ?\DateTimeImmutable
    {
        return $this->delegationDate;
    }

    public function setDelegationDate(?\DateTimeImmutable $delegationDate): static
    {
        $this->delegationDate = $delegationDate;

        return $this;
    }

    public function getRegistryOperator(): ?string
    {
        return $this->registryOperator;
    }

    public function setRegistryOperator(?string $registryOperator): static
    {
        $this->registryOperator = $registryOperator;

        return $this;
    }

    public function getRemovalDate(): ?\DateTimeImmutable
    {
        return $this->removalDate;
    }

    public function setRemovalDate(?\DateTimeImmutable $removalDate): static
    {
        $this->removalDate = $removalDate;

        return $this;
    }

    public function isSpecification13(): ?bool
    {
        return $this->specification13;
    }

    public function setSpecification13(?bool $specification13): static
    {
        $this->specification13 = $specification13;

        return $this;
    }

    public function getType(): ?TldType
    {
        return $this->type;
    }

    public function setType(?TldType $type): static
    {
        $this->type = $type;

        return $this;
    }
}
