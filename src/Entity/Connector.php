<?php

namespace App\Entity;

use App\Config\ConnectorProvider;
use App\Repository\ConnectorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;

#[ORM\Entity(repositoryClass: ConnectorRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'provider', enumType: ConnectorProvider::class)]
#[DiscriminatorMap([ConnectorProvider::OVH->value => OVHConnector::class])]
class Connector
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    private ?string $provider = null;

    #[ORM\ManyToOne(inversedBy: 'connectors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private array $authData = [];

    /**
     * @var Collection<int, WatchListTrigger>
     */
    #[ORM\OneToMany(targetEntity: WatchListTrigger::class, mappedBy: 'connector')]
    private Collection $watchListTriggers;

    public function __construct()
    {
        $this->watchListTriggers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
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

}
