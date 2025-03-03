<?php

namespace App\Service\Connector;

use App\Dto\Connector\DefaultProviderDto;
use App\Entity\Domain;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    /** @var class-string */
    protected string $dtoClass = DefaultProviderDto::class;
    protected DefaultProviderDto $authData;

    public function __construct(
        protected CacheItemPoolInterface $cacheItemPool,
        private readonly DenormalizerInterface&NormalizerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Perform a static check of the connector data.
     * To be valid, the data fields must match the Provider and the conditions must be accepted.
     * User consent is checked here.
     *
     * @param array $authData raw authentication data as supplied by the user
     *
     * @return DefaultProviderDto a cleaned up version of the authentication data
     *
     * @throws HttpException      when the user does not accept the necessary conditions
     * @throws ExceptionInterface
     */
    private function verifyAuthData(array $authData): DefaultProviderDto
    {
        /** @var DefaultProviderDto $data */
        $data = $this->serializer->denormalize($this->verifyLegalAuthData($authData), $this->dtoClass);
        $violations = $this->validator->validate($data);

        if ($violations->count() > 0) {
            throw new BadRequestHttpException((string) $violations);
        }

        return $data;
    }

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
    abstract protected function assertAuthentication(): void; // TODO use dedicated exception type

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
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function authenticate(array $authData): DefaultProviderDto
    {
        $this->authData = $this->verifyAuthData($authData);
        $this->assertAuthentication();

        return $this->authData;
    }

    abstract protected function getCachedTldList(): CacheItemInterface;

    abstract protected function getSupportedTldList(): array;
}
