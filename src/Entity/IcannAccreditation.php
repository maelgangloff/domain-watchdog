<?php

namespace App\Entity;

use App\Config\RegistrarStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embeddable;
use Symfony\Component\Serializer\Attribute\Groups;

#[Embeddable]
class IcannAccreditation
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['entity:item', 'entity:list', 'domain:item'])]
    private ?string $registrarName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['entity:item', 'domain:item'])]
    private ?string $rdapBaseUrl = null;

    #[ORM\Column(nullable: true, enumType: RegistrarStatus::class)]
    #[Groups(['entity:item', 'entity:list', 'domain:item'])]
    private ?RegistrarStatus $status = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['entity:item', 'entity:list', 'domain:item'])]
    private ?\DateTimeImmutable $updated = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['entity:item', 'entity:list', 'domain:item'])]
    private ?\DateTimeImmutable $date = null;

    public function getRegistrarName(): ?string
    {
        return $this->registrarName;
    }

    public function setRegistrarName(?string $registrarName): static
    {
        $this->registrarName = $registrarName;

        return $this;
    }

    public function getRdapBaseUrl(): ?string
    {
        return $this->rdapBaseUrl;
    }

    public function setRdapBaseUrl(?string $rdapBaseUrl): static
    {
        $this->rdapBaseUrl = $rdapBaseUrl;

        return $this;
    }

    public function getStatus(): ?RegistrarStatus
    {
        return $this->status;
    }

    public function setStatus(?RegistrarStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUpdated(): ?\DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeImmutable $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }
}
