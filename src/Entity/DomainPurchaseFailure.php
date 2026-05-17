<?php

namespace App\Entity;

use App\Config\DomainPurchaseFailureReason;
use App\Repository\DomainPurchaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainPurchaseRepository::class)]
class DomainPurchaseFailure extends DomainPurchase
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exceptionClass = null;

    #[ORM\Column(nullable: true, enumType: DomainPurchaseFailureReason::class)]
    private ?DomainPurchaseFailureReason $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $exceptionMessage = null;

    public function getExceptionClass(): ?string
    {
        return $this->exceptionClass;
    }

    public function setExceptionClass(?string $exceptionClass): self
    {
        $this->exceptionClass = $exceptionClass;

        return $this;
    }

    public function getReason(): ?DomainPurchaseFailureReason
    {
        return $this->reason;
    }

    public function setReason(?DomainPurchaseFailureReason $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(?string $exceptionMessage): self
    {
        $this->exceptionMessage = $exceptionMessage;

        return $this;
    }
}
