<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\MappedSuperclass]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['event:list'])]
    private ?string $action = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['event:list'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Groups(['event:list'])]
    private ?bool $deleted;

    public function __construct()
    {
        $this->deleted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
