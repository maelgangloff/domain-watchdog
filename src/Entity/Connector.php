<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Config\ConnectorProvider;
use App\Repository\ConnectorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Connector',
    operations: [
        new GetCollection(
            routeName: 'connector_get_all_mine',
            normalizationContext: ['groups' => 'connector:list'],
            name: 'get_all_mine',
        ),
        new Get(
            normalizationContext: ['groups' => 'connector:list']
        ),
        new Post(
            routeName: 'connector_create', normalizationContext: ['groups' => ['connector:create', 'connector:list']],
            denormalizationContext: ['groups' => 'connector:create'],
            name: 'create'
        ),
        new Delete()
    ]
)]
#[ORM\Entity(repositoryClass: ConnectorRepository::class)]
class Connector
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['connector:list', 'watchlist:list'])]
    private ?string $id;

    #[ORM\ManyToOne(inversedBy: 'connectors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    #[Groups(['connector:list', 'connector:create', 'watchlist:list'])]
    #[ORM\Column(enumType: ConnectorProvider::class)]
    private ?ConnectorProvider $provider = null;

    #[Groups(['connector:create'])]
    #[ORM\Column]
    private array $authData = [];

    /**
     * @var Collection<int, WatchListTrigger>
     */
    #[ORM\OneToMany(targetEntity: WatchListTrigger::class, mappedBy: 'connector')]
    private Collection $watchListTriggers;


    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->watchListTriggers = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAuthData(): array
    {
        return $this->authData;
    }

    public function setAuthData(array $authData): static
    {
        $this->authData = $authData;

        return $this;
    }

    /**
     * @return Collection<int, WatchListTrigger>
     */
    public function getWatchListTriggers(): Collection
    {
        return $this->watchListTriggers;
    }

    public function addWatchListTrigger(WatchListTrigger $watchListTrigger): static
    {
        if (!$this->watchListTriggers->contains($watchListTrigger)) {
            $this->watchListTriggers->add($watchListTrigger);
            $watchListTrigger->setConnector($this);
        }

        return $this;
    }

    public function removeWatchListTrigger(WatchListTrigger $watchListTrigger): static
    {
        if ($this->watchListTriggers->removeElement($watchListTrigger)) {
            // set the owning side to null (unless already changed)
            if ($watchListTrigger->getConnector() === $this) {
                $watchListTrigger->setConnector(null);
            }
        }

        return $this;
    }

    public function getProvider(): ?ConnectorProvider
    {
        return $this->provider;
    }

    public function setProvider(ConnectorProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

}
