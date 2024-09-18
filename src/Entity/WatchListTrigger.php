<?php

namespace App\Entity;

use App\Config\TriggerAction;
use App\Repository\EventTriggerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EventTriggerRepository::class)]
class WatchListTrigger
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?string $event = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WatchList::class, cascade: ['persist'], inversedBy: 'watchListTriggers')]
    #[ORM\JoinColumn(referencedColumnName: 'token', nullable: false, onDelete: 'CASCADE')]
    private ?WatchList $watchList = null;

    #[ORM\Id]
    #[ORM\Column(enumType: TriggerAction::class)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
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
