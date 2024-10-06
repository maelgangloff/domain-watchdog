<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Exception;
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
     * Checks the consistency of the authentication data and returns sanitized data.
     *
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return array a cleaned up version of the authentication data
     */
    abstract protected function verifyAuthData(array $authData): array;

    /**
     * Throws an error if the authentication data is incorrect.
     *
     * @throws \Exception when the registrar denies the authentication
     */
    abstract protected function assertAuthentication(): void; // TODO use dedicated exception type

    /**
     * Order a domain name.
     *
     * @param Domain $domain The domain name to be ordered
     * @param bool   $dryRun If true, the domain name will not be purchased
     */
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
     * Authenticates the user to the registrar API.
     *
     * @throws \Exception when the registrar denies the authentication
     */
    public function authenticate(array $authData): array
    {
        $this->authData = $this->verifyAuthData($authData);
        $this->assertAuthentication();

        return $this->authData;
    }

    abstract protected function getCachedTldList(): CacheItemInterface;

    abstract protected function getSupportedTldList(): array;
}
