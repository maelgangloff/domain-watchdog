<?php

namespace App\Entity;

use App\Config\DnsKey\Algorithm;
use App\Config\DnsKey\DigestType;
use App\Repository\DnsKeyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DnsKeyRepository::class)]
class DnsKey
{
    #[ORM\Column(nullable: true, enumType: Algorithm::class)]
    #[Groups(['ds:list'])]
    #[ORM\Id]
    private ?Algorithm $algorithm;

    #[ORM\Column(enumType: DigestType::class)]
    #[Groups(['ds:list'])]
    #[ORM\Id]
    private ?DigestType $digestType;

    #[ORM\Column(type: Types::BINARY)]
    #[Groups(['ds:list'])]
    #[ORM\Id]
    private $keyTag;

    #[ORM\ManyToOne(inversedBy: 'dnsKey')]
    #[ORM\JoinColumn(referencedColumnName: 'ldh_name', nullable: false)]
    #[Groups(['ds:list', 'ds:item'])]
    #[ORM\Id]
    private ?Domain $domain = null;

    #[ORM\Column(type: Types::BLOB)]
    #[Groups(['ds:list'])]
    #[ORM\Id]
    private $digest;

    public function getAlgorithm(): ?Algorithm
    {
        return $this->algorithm;
    }

    public function setAlgorithm(?Algorithm $algorithm): static
    {
        $this->algorithm = $algorithm;

        return $this;
    }

    public function getDigestType(): ?DigestType
    {
        return $this->digestType;
    }

    public function setDigestType(DigestType $digestType): static
    {
        $this->digestType = $digestType;

        return $this;
    }

    public function getKeyTag(): string
    {
        $value = $this->keyTag;

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return unpack('n', $value)[1];
    }

    public function setKeyTag($keyTag): static
    {
        $this->keyTag = $keyTag;

        return $this;
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

    public function getDigest(): string
    {
        $value = $this->digest;

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return strtoupper(bin2hex($value));
    }

    public function setDigest($digest): static
    {
        $this->digest = $digest;

        return $this;
    }
}
