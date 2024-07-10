<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'ldhname')]
    private ?Domain $domain = null;

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
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
