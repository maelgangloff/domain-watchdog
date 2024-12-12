<?php

namespace App\Entity;

use App\Repository\DomainStatusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $addStatus = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $deleteStatus = [];

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('now');
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

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
