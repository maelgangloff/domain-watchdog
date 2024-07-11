<?php

namespace App\Entity;

use App\Config\EventAction;
use App\Repository\DomainEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEventRepository::class)]
class DomainEvent
{
    #[ORM\Id]
    #[ORM\Column(enumType: EventAction::class)]
    private ?EventAction $action = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Domain::class, inversedBy: 'events')]
    #[ORM\JoinColumn(referencedColumnName: 'handle', nullable: false)]
    private ?Domain $domain = null;

    public function getAction(): ?EventAction
    {
        return $this->action;
    }

    public function setAction(EventAction $action): static
    {
        $this->action = $action;

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

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }
}
