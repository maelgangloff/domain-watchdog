<?php

namespace App\Entity;

use App\Config\ConnectorProvider;
use App\Repository\ConnectorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConnectorRepository::class)]
class Connector
{
    #[ORM\Id]
    #[ORM\Column(enumType: ConnectorProvider::class)]
    private ?ConnectorProvider $provider = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'connectors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private array $authData = [];


    public function getProvider(): ?ConnectorProvider
    {
        return $this->provider;
    }

    public function setProvider(ConnectorProvider $provider): static
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
