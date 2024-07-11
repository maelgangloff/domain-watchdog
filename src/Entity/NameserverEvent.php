<?php

namespace App\Entity;

use App\Config\EventAction;
use App\Repository\NameserverEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameserverEventRepository::class)]
class NameserverEvent
{
    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'nameserverEvents')]
    #[ORM\JoinColumn(referencedColumnName: 'handle', nullable: false)]
    private ?Nameserver $nameserver = null;

    #[ORM\Id]
    #[ORM\Column(enumType: EventAction::class)]
    private ?EventAction $action = null;

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNameserver(): ?Nameserver
    {
        return $this->nameserver;
    }

    public function setNameserver(?Nameserver $nameserver): static
    {
        $this->nameserver = $nameserver;

        return $this;
    }

    public function getAction(): ?EventAction
    {
        return $this->action;
    }

    public function setAction(EventAction $action): static
    {
        $this->action = $action;

        return $this;
    }
}
