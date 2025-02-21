<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;


class EppClientProvider extends AbstractProvider implements EppClientProviderInterface
{
    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
    )
    {
        parent::__construct($cacheItemPool);
    }

    protected function verifySpecificAuthData(array $authData): array
    {
        // TODO: Create DTO for each authData schema

        return $authData;
    }

    protected function assertAuthentication(): void
    {
        //TODO: implementation
    }

    public function orderDomain(Domain $domain, bool $dryRun): void
    {
        //TODO: implementation
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.epp.supported-tld');
    }

    protected function getSupportedTldList(): array
    {
        return [];
    }

    public function isSupported(Domain ...$domainList): bool
    {
        if (0 === count($domainList)) {
            return true;
        }
        $tld = $domainList[0]->getTld();

        foreach ($domainList as $domain) {
            if ($domain->getTld() !== $tld) {
                return false;
            }
        }

        return true;
    }

    public function checkDomains(Domain ...$domains): array
    {
        //TODO : implementation
        return [];
    }
}
