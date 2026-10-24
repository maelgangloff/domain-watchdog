<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\StatisticsController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/stats',
            controller: StatisticsController::class,
            openapiContext: [
                'summary' => 'Statistics of the server',
                'description' => 'This endpoint allows you to retrieve key statistics from this API server. For example, you can retrieve the number of RDAP requests made, the number of domains in the database, the number of domains successfully purchased, the success rate, etc.',
            ],
            shortName: 'Statistics',
            read: false,
        ),
    ]
)]
class Statistics
{
    private ?int $rdapQueries = null;
    private ?int $alertSent = null;
    private ?int $domainPurchaseSuccess = null;
    private ?int $domainPurchaseFailure = null;

    private ?array $domainCount = null;
    private ?int $domainTracked = null;
    private ?int $domainCountTotal = null;

    public function getRdapQueries(): ?int
    {
        return $this->rdapQueries;
    }

    public function setRdapQueries(?int $rdapQueries): static
    {
        $this->rdapQueries = $rdapQueries;

        return $this;
    }

    public function getAlertSent(): ?int
    {
        return $this->alertSent;
    }

    public function setAlertSent(?int $alertSent): static
    {
        $this->alertSent = $alertSent;

        return $this;
    }

    public function getDomainCount(): ?array
    {
        return $this->domainCount;
    }

    public function setDomainCount(?array $domainCount): static
    {
        $this->domainCount = $domainCount;

        return $this;
    }

    public function getDomainCountTotal(): ?int
    {
        return $this->domainCountTotal;
    }

    public function setDomainCountTotal(?int $domainCountTotal): static
    {
        $this->domainCountTotal = $domainCountTotal;

        return $this;
    }

    public function getDomainTracked(): ?int
    {
        return $this->domainTracked;
    }

    public function setDomainTracked(?int $domainTracked): static
    {
        $this->domainTracked = $domainTracked;

        return $this;
    }

    public function getDomainPurchaseSuccess(): ?int
    {
        return $this->domainPurchaseSuccess;
    }

    public function setDomainPurchaseSuccess(?int $domainPurchaseSuccess): self
    {
        $this->domainPurchaseSuccess = $domainPurchaseSuccess;

        return $this;
    }

    public function getDomainPurchaseFailure(): ?int
    {
        return $this->domainPurchaseFailure;
    }

    public function setDomainPurchaseFailure(?int $domainPurchaseFailure): self
    {
        $this->domainPurchaseFailure = $domainPurchaseFailure;

        return $this;
    }
}
