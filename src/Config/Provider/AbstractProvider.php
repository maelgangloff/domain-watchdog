<?php

namespace App\Config\Provider;

use App\Entity\Domain;
use App\Entity\Tld;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractProvider
{
    public function __construct(
        protected array $authData,
        protected HttpClientInterface $client,
        protected CacheItemPoolInterface $cacheItemPool,
        protected KernelInterface $kernel
    ) {
    }

    abstract public static function verifyAuthData(array $authData, HttpClientInterface $client): array;

    abstract public function orderDomain(Domain $domain, bool $dryRun): void;

    public function isSupported(Tld ...$tldList): bool
    {
        $item = $this->getCachedTldList();
        if (!$item->isHit()) {
            $supportedTldList = $this->getSupportedTldList();
            $item
                ->set($supportedTldList)
                ->expiresAfter(new \DateInterval('P1M'));
            $this->cacheItemPool->saveDeferred($item);
        } else {
            $supportedTldList = $item->get();
        }

        /** @var string $tldString */
        foreach (array_unique(array_map(fn (Tld $tld) => $tld->getTld(), $tldList)) as $tldString) {
            if (!in_array($tldString, $supportedTldList)) {
                return false;
            }
        }

        return true;
    }

    abstract protected function getCachedTldList(): CacheItemInterface;

    abstract protected function getSupportedTldList(): array;
}
