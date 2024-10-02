<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The typical flow of a provider will go as follows:
 *
 * MyProvider $provider; // gotten from DI
 * $provider->authenticate($authData);
 * $provider->orderDomain($domain, $dryRun);
 */
abstract class AbstractProvider
{
    protected array $authData;

    public function __construct(
        protected CacheItemPoolInterface $cacheItemPool
    ) {
    }

    /**
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return array a cleaned up version of the authentication data
     */
    abstract public function verifyAuthData(array $authData): array;

    /**
     * @throws \Exception when the registrar denies the authentication
     */
    abstract public function assertAuthentication(): void; // TODO use dedicated exception type

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

    /**
     * @throws \Exception
     */
    public function authenticate(array $authData): void
    {
        $this->authData = $this->verifyAuthData($authData);
        $this->assertAuthentication();
    }

    abstract protected function getCachedTldList(): CacheItemInterface;

    abstract protected function getSupportedTldList(): array;
}
