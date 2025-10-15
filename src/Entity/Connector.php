<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Config\ConnectorProvider;
use App\Repository\ConnectorRepository;
use App\State\ConnectorCreateProcessor;
use App\State\ConnectorDeleteProcessor;
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
            normalizationContext: ['groups' => 'connector:list'],
            security: 'object.getUser() == user'
        ),
        new Post(
            normalizationContext: ['groups' => ['connector:create', 'connector:list']],
            denormalizationContext: ['groups' => 'connector:create'],
            processor: ConnectorCreateProcessor::class
        ),
        new Delete(
            security: 'object.getUser() == user',
            processor: ConnectorDeleteProcessor::class
        ),
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
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?User $user = null;

    #[Groups(['connector:list', 'connector:create', 'watchlist:list'])]
    #[ORM\Column(enumType: ConnectorProvider::class)]
    private ?ConnectorProvider $provider = null;

    #[Groups(['connector:create'])]
    #[ORM\Column]
    private array $authData = [];

    /**
     * @var Collection<int, WatchList>
     */
    #[ORM\OneToMany(targetEntity: WatchList::class, mappedBy: 'connector')]
    private Collection $watchLists;

    #[Groups(['connector:list', 'watchlist:list'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['connector:list'])]
    protected int $watchlistCount;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->watchLists = new ArrayCollection();
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

    public function getProvider(): ?ConnectorProvider
    {
        return $this->provider;
    }

    public function setProvider(ConnectorProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return Collection<int, WatchList>
     */
    public function getWatchLists(): Collection
    {
        return $this->watchLists;
    }

    public function addWatchList(WatchList $watchList): static
    {
        if (!$this->watchLists->contains($watchList)) {
            $this->watchLists->add($watchList);
            $watchList->setConnector($this);
        }

        return $this;
    }

    public function removeWatchList(WatchList $watchList): static
    {
        if ($this->watchLists->removeElement($watchList)) {
            // set the owning side to null (unless already changed)
            if ($watchList->getConnector() === $this) {
                $watchList->setConnector(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getWatchlistCount(): ?int
    {
        return $this->watchLists->count();
    }
}
