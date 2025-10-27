<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Config\RegistrarStatus;
use App\Repository\IcannAccreditationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/icann-accreditations',
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'status',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'enum' => ['Accredited', 'Terminated', 'Reserved'],
                            ],
                        ],
                        'style' => 'form',
                        'explode' => true,
                        'description' => 'Filter by ICANN accreditation status',
                    ],
                ],
            ],
            shortName: 'ICANN Accreditation',
            description: 'ICANN Registrar IDs list',
            normalizationContext: ['groups' => ['icann:list']]
        ),
    ]
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'status' => 'exact',
    ]
)]
#[ORM\Entity(repositoryClass: IcannAccreditationRepository::class)]
class IcannAccreditation
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(['icann:item', 'icann:list', 'domain:item'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['icann:item', 'icann:list', 'domain:item'])]
    private ?string $registrarName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['icann:item'])]
    private ?string $rdapBaseUrl = null;

    #[ORM\Column(nullable: true, enumType: RegistrarStatus::class)]
    #[Groups(['icann:item', 'icann:list', 'domain:item'])]
    private ?RegistrarStatus $status = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['icann:item', 'icann:list', 'domain:item'])]
    private ?\DateTimeImmutable $updated = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['icann:item', 'icann:list', 'domain:item'])]
    private ?\DateTimeImmutable $date = null;

    /**
     * @var Collection<int, Entity>
     */
    #[ORM\OneToMany(targetEntity: Entity::class, mappedBy: 'icannAccreditation')]
    private Collection $entities;

    public function __construct()
    {
        $this->entities = new ArrayCollection();
    }

    public function getRegistrarName(): ?string
    {
        return $this->registrarName;
    }

    public function setRegistrarName(?string $registrarName): static
    {
        $this->registrarName = $registrarName;

        return $this;
    }

    public function getRdapBaseUrl(): ?string
    {
        return $this->rdapBaseUrl;
    }

    public function setRdapBaseUrl(?string $rdapBaseUrl): static
    {
        $this->rdapBaseUrl = $rdapBaseUrl;

        return $this;
    }

    public function getStatus(): ?RegistrarStatus
    {
        return $this->status;
    }

    public function setStatus(?RegistrarStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUpdated(): ?\DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeImmutable $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Collection<int, Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    public function addEntity(Entity $entity): static
    {
        if (!$this->entities->contains($entity)) {
            $this->entities->add($entity);
            $entity->setIcannAccreditation($this);
        }

        return $this;
    }

    public function removeEntity(Entity $entity): static
    {
        if ($this->entities->removeElement($entity)) {
            // set the owning side to null (unless already changed)
            if ($entity->getIcannAccreditation() === $this) {
                $entity->setIcannAccreditation(null);
            }
        }

        return $this;
    }
}
