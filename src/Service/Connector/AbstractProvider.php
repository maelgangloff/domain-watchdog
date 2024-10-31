<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The typical flow of a provider will go as follows:
 *
 * MyProvider $provider; // gotten from DI
 * $provider->authenticate($authData);
 * $provider->orderDomain($domain, $dryRun);
 */
#[Autoconfigure(public: true)]
abstract class AbstractProvider
{
    protected array $authData;

    public function __construct(
        protected CacheItemPoolInterface $cacheItemPool,
    ) {
    }

    /**
     * Perform a static check of the connector data.
     * To be valid, the data fields must match the Provider and the conditions must be accepted.
     * User consent is checked here.
     *
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return array a cleaned up version of the authentication data
     *
     * @throws HttpException when the user does not accept the necessary conditions
     */
    public function verifyAuthData(array $authData): array
    {
        return [
            ...$this->verifySpecificAuthData($this->verifyLegalAuthData($authData)),
            'acceptConditions' => $authData['acceptConditions'],
            'ownerLegalAge' => $authData['ownerLegalAge'],
            'waiveRetractationPeriod' => $authData['waiveRetractationPeriod'],
        ];
    }

    /**
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return array specific authentication data
     */
    abstract protected function verifySpecificAuthData(array $authData): array;

    /**
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return array raw authentication data as supplied by the user
     *
     * @throws HttpException when the user does not accept the necessary conditions
     */
    private function verifyLegalAuthData(array $authData): array
    {
        $acceptConditions = $authData['acceptConditions'];
        $ownerLegalAge = $authData['ownerLegalAge'];
        $waiveRetractationPeriod = $authData['waiveRetractationPeriod'];

        if (true !== $acceptConditions
            || true !== $ownerLegalAge
            || true !== $waiveRetractationPeriod) {
            throw new HttpException(451, 'The user has not given explicit consent');
        }

        return $authData;
    }

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
