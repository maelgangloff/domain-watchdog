<?php

namespace App\Entity;

use App\Repository\RdapServerRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RdapServerRepository::class)]
class RdapServer
{
    #[ORM\Id]
    #[ORM\Column(length: 63)]
    private ?string $tld = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new DateTimeImmutable('now');
    }

    public function getTld(): ?string
    {
        return $this->tld;
    }

    public function setTld(string $tld): static
    {
        $this->tld = $tld;

        return $this;
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

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->setUpdatedAt(new DateTimeImmutable('now'));
    }
}
