<?php

namespace App\Entity;

use App\Repository\RdapServerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RdapServerRepository::class)]
class RdapServer
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    #[Groups(['domain:item'])]
    private ?string $url = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'rdapServers')]
    #[ORM\JoinColumn(referencedColumnName: 'tld', nullable: false)]
    private ?Tld $tld = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->setUpdatedAt(new \DateTimeImmutable('now'));
    }

    public function getTld(): ?Tld
    {
        return $this->tld;
    }

    public function setTld(?Tld $tld): static
    {
        $this->tld = $tld;

        return $this;
    }
}
