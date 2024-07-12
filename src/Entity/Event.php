<?php

namespace App\Entity;

use App\Config\EventAction;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: EventAction::class)]
    private ?EventAction $action = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $date = null;


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

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

}
