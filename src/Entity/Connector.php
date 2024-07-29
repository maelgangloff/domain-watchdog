<?php

namespace App\Entity;

use App\Config\ConnectorProvider;
use App\Repository\ConnectorRepository;
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

}
