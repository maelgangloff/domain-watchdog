<?php

namespace App\Entity;

use App\Config\EventAction;
use App\Config\TriggerAction;
use App\Repository\EventTriggerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EventTriggerRepository::class)]
class WatchListTrigger
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['watchlist:item', 'watchlist:create', 'watchlist:update'])]
    private ?string $event = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WatchList::class, inversedBy: 'watchListTriggers')]
    #[ORM\JoinColumn(referencedColumnName: 'token', nullable: false)]
    private ?WatchList $watchList = null;

    #[ORM\Id]
    #[ORM\Column(enumType: TriggerAction::class)]
    #[Groups(['watchlist:item', 'watchlist:create', 'watchlist:update'])]
    private ?TriggerAction $action = null;

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getWatchList(): ?WatchList
    {
        return $this->watchList;
    }

    public function setWatchList(?WatchList $watchList): static
    {
        $this->watchList = $watchList;

        return $this;
    }

    public function getAction(): ?TriggerAction
    {
        return $this->action;
    }

    public function setAction(TriggerAction $action): static
    {
        $this->action = $action;

        return $this;
    }
}
