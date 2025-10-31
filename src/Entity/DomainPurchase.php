<?php

namespace App\Entity;

use App\Config\ConnectorProvider;
use App\Repository\DomainPurchaseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DomainPurchaseRepository::class)]
class DomainPurchase
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?string $id;

    #[ORM\ManyToOne(inversedBy: 'domainPurchases')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    private ?Domain $domain = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $domainUpdatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $domainOrderedAt = null;

    #[ORM\Column(enumType: ConnectorProvider::class)]
    private ?ConnectorProvider $connectorProvider = null;

    #[ORM\ManyToOne(inversedBy: 'domainPurchases')]
    private ?Connector $connector = null;

    #[ORM\ManyToOne(inversedBy: 'domainPurchases')]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $domainDeletedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomainUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->domainUpdatedAt;
    }

    public function setDomainUpdatedAt(\DateTimeImmutable $domainUpdatedAt): static
    {
        $this->domainUpdatedAt = $domainUpdatedAt;

        return $this;
    }

    public function getDomainOrderedAt(): ?\DateTimeImmutable
    {
        return $this->domainOrderedAt;
    }

    public function setDomainOrderedAt(?\DateTimeImmutable $domainOrderedAt): static
    {
        $this->domainOrderedAt = $domainOrderedAt;

        return $this;
    }

    public function getConnectorProvider(): ?ConnectorProvider
    {
        return $this->connectorProvider;
    }

    public function setConnectorProvider(ConnectorProvider $connectorProvider): static
    {
        $this->connectorProvider = $connectorProvider;

        return $this;
    }

    public function getConnector(): ?Connector
    {
        return $this->connector;
    }

    public function setConnector(?Connector $connector): static
    {
        $this->connector = $connector;

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

    public function getDomainDeletedAt(): ?\DateTimeImmutable
    {
        return $this->domainDeletedAt;
    }

    public function setDomainDeletedAt(\DateTimeImmutable $domainDeletedAt): static
    {
        $this->domainDeletedAt = $domainDeletedAt;

        return $this;
    }
}
