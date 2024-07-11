<?php

namespace App\Entity;

use App\Config\EventAction;
use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: EventAction::class)]
    private ?EventAction $action = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\ManyToOne(targetEntity: Entity::class, inversedBy: 'events')]
    #[ORM\JoinColumn(referencedColumnName: 'handle', nullable: false)]
    private ?Entity $entity = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function setEntity(?Entity $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

}
