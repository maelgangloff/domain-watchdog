<?php

namespace App\Config\Provider;

use App\Entity\Domain;
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

    public function isSupported(Domain ...$domainList): bool
    {
        $item = $this->getCachedTldList();
        if (!$item->isHit()) {
            $supportedTldList = $this->getSupportedTldList();
            $item
                ->set($supportedTldList)
                ->expiresAfter(new \DateInterval('PT1H'));
            $this->cacheItemPool->saveDeferred($item);
        } else {
            $supportedTldList = $item->get();
        }

        $extensionList = [];
        foreach ($domainList as $domain) {
            // We want to check the support of TLDs and SLDs here.
            // For example, it is not enough for the Connector to support .fr for it to support the domain name example.asso.fr.
            // It must support .asso.fr.
            $extension = explode('.', $domain->getLdhName(), 2)[1];
            if (!in_array($extension, $extensionList)) {
                $extensionList[] = $extension;
            }
        }

        foreach ($extensionList as $extension) {
            if (!in_array($extension, $supportedTldList)) {
                return false;
            }
        }

        return true;
    }

    abstract protected function getCachedTldList(): CacheItemInterface;

    abstract protected function getSupportedTldList(): array;
}
