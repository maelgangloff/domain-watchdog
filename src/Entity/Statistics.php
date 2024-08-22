<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\StatisticsController;
use Psr\Cache\CacheItemPoolInterface;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/stats',
            controller: StatisticsController::class,
            shortName: 'Statistics',
            read: false,
        ),
    ]
)]
class Statistics
{
    private ?int $rdapQueries = null;
    private ?int $alertSent = null;
    private ?int $domainPurchased = null;
    private ?int $domainPurchaseFailed = null;

    private ?array $domainCount = null;
    private ?int $domainCountTotal = null;
    private ?int $watchlistCount = null;

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

    public function getDomainPurchased(): ?int
    {
        return $this->domainPurchased;
    }

    public function setDomainPurchased(?int $domainPurchased): static
    {
        $this->domainPurchased = $domainPurchased;

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

    public function getWatchlistCount(): ?int
    {
        return $this->watchlistCount;
    }

    public function setWatchlistCount(?int $watchlistCount): static
    {
        $this->watchlistCount = $watchlistCount;

        return $this;
    }

    public function getDomainCountTotal(): ?int
    {
        return $this->domainCountTotal;
    }

    public function setDomainCountTotal(?int $domainCountTotal): void
    {
        $this->domainCountTotal = $domainCountTotal;
    }

    public function getDomainPurchaseFailed(): ?int
    {
        return $this->domainPurchaseFailed;
    }

    public function setDomainPurchaseFailed(?int $domainPurchaseFailed): static
    {
        $this->domainPurchaseFailed = $domainPurchaseFailed;

        return $this;
    }

    public static function updateRDAPQueriesStat(CacheItemPoolInterface $pool, string $key): bool
    {
        try {
            $item = $pool->getItem($key);
            $item->set(($item->get() ?? 0) + 1);

            return $pool->save($item);
        } catch (\Throwable) {
        }

        return false;
    }
}
