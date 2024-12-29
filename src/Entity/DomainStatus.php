<?php

namespace App\Entity;

use App\Repository\DomainStatusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DomainStatusRepository::class)]
class DomainStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'domainStatuses')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    private ?Domain $domain = null;

    #[ORM\Column]
    #[Groups(['domain:item'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['domain:item'])]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['domain:item'])]
    private array $addStatus = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Groups(['domain:item'])]
    private array $deleteStatus = [];

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('now');
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAddStatus(): array
    {
        return $this->addStatus;
    }

    public function setAddStatus(array $addStatus): static
    {
        $this->addStatus = $addStatus;

        return $this;
    }

    public function getDeleteStatus(): array
    {
        return $this->deleteStatus;
    }

    public function setDeleteStatus(array $deleteStatus): static
    {
        $this->deleteStatus = $deleteStatus;

        return $this;
    }
}
