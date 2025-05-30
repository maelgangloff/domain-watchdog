<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Config\TriggerAction;
use App\Repository\EventTriggerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EventTriggerRepository::class)]
#[ApiResource(
    uriTemplate: '/watchlists/{watchListId}/triggers/{action}/{event}',
    operations: [
        new Get(),
        new GetCollection(
            uriTemplate: '/watchlists/{watchListId}/triggers',
            uriVariables: [
                'watchListId' => new Link(fromProperty: 'token', toProperty: 'watchList', fromClass: WatchList::class),
            ],
        ),
        new Post(
            uriTemplate: '/watchlist-triggers',
            uriVariables: [],
            security: 'true'
        ),
        new Delete(),
    ],
    uriVariables: [
        'watchListId' => new Link(fromProperty: 'token', toProperty: 'watchList', fromClass: WatchList::class),
        'action' => 'action',
        'event' => 'event',
    ],
    security: 'object.getWatchList().user == user',
)]
class WatchListTrigger
{
    #[ORM\Id]
    #[ORM\Column(length: 255, nullable: false)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?string $event;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WatchList::class, inversedBy: 'watchListTriggers')]
    #[ORM\JoinColumn(referencedColumnName: 'token', nullable: false, onDelete: 'CASCADE')]
    private ?WatchList $watchList;

    #[ORM\Id]
    #[ORM\Column(nullable: false, enumType: TriggerAction::class)]
    #[Groups(['watchlist:list', 'watchlist:item', 'watchlist:create'])]
    private ?TriggerAction $action;

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
