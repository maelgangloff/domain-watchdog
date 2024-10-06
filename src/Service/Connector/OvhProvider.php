<?php

namespace App\Service\Connector;

use App\Entity\Domain;
use GuzzleHttp\Exception\ClientException;
use Ovh\Api;
use Ovh\Exceptions\InvalidParameterException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OvhProvider extends AbstractProvider
{
    public const REQUIRED_ROUTES = [
        [
            'method' => 'GET',
            'path' => '/domain/extensions',
        ],
        [
            'method' => 'GET',
            'path' => '/order/cart',
        ],
        [
            'method' => 'GET',
            'path' => '/order/cart/*',
        ],
        [
            'method' => 'POST',
            'path' => '/order/cart',
        ],
        [
            'method' => 'POST',
            'path' => '/order/cart/*',
        ],
        [
            'method' => 'DELETE',
            'path' => '/order/cart/*',
        ],
    ];

    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        parent::__construct($cacheItemPool);
    }

    /**
     * Order a domain name with the OVH API.
     *
     * @throws \Exception
     */
    public function orderDomain(Domain $domain, bool $dryRun = false): void
    {
        $ldhName = $domain->getLdhName();
        if (!$ldhName) {
            throw new \InvalidArgumentException('Domain name cannot be null');
        }

        $acceptConditions = $this->authData['acceptConditions'];
        $ownerLegalAge = $this->authData['ownerLegalAge'];
        $waiveRetractationPeriod = $this->authData['waiveRetractationPeriod'];

        $conn = new Api(
            $this->authData['appKey'],
            $this->authData['appSecret'],
            $this->authData['apiEndpoint'],
            $this->authData['consumerKey']
        );

        $cart = $conn->post('/order/cart', [
            'ovhSubsidiary' => $this->authData['ovhSubsidiary'],
            'description' => 'Domain Watchdog',
        ]);
        $cartId = $cart['cartId'];

        $offers = $conn->get("/order/cart/{$cartId}/domain", [
            'domain' => $ldhName,
        ]);

        $pricingModes = ['create-default'];
        if ('create-default' !== $this->authData['pricingMode']) {
            $pricingModes[] = $this->authData['pricingMode'];
        }

        $offer = array_filter($offers, fn ($offer) => 'create' === $offer['action']
            && true === $offer['orderable']
            && in_array($offer['pricingMode'], $pricingModes)
        );
        if (empty($offer)) {
            $conn->delete("/order/cart/{$cartId}");
            throw new \InvalidArgumentException('Cannot buy this domain name');
        }

        $item = $conn->post("/order/cart/{$cartId}/domain", [
            'domain' => $ldhName,
            'duration' => 'P1Y',
        ]);
        $itemId = $item['itemId'];

        // $conn->get("/order/cart/{$cartId}/summary");
        $conn->post("/order/cart/{$cartId}/assign");
        $conn->get("/order/cart/{$cartId}/item/{$itemId}/requiredConfiguration");

        $configuration = [
            'ACCEPT_CONDITIONS' => $acceptConditions,
            'OWNER_LEGAL_AGE' => $ownerLegalAge,
        ];

        foreach ($configuration as $label => $value) {
            $conn->post("/order/cart/{$cartId}/item/{$itemId}/configuration", [
                'cartId' => $cartId,
                'itemId' => $itemId,
                'label' => $label,
                'value' => $value,
            ]);
        }
        $conn->get("/order/cart/{$cartId}/checkout");

        if ($dryRun) {
            return;
        }
        $conn->post("/order/cart/{$cartId}/checkout", [
            'autoPayWithPreferredPaymentMethod' => true,
            'waiveRetractationPeriod' => $waiveRetractationPeriod,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function verifyAuthData(array $authData): array
    {
        $appKey = $authData['appKey'];
        $appSecret = $authData['appSecret'];
        $apiEndpoint = $authData['apiEndpoint'];
        $consumerKey = $authData['consumerKey'];
        $ovhSubsidiary = $authData['ovhSubsidiary'];
        $pricingMode = $authData['pricingMode'];

        $acceptConditions = $authData['acceptConditions'];
        $ownerLegalAge = $authData['ownerLegalAge'];
        $waiveRetractationPeriod = $authData['waiveRetractationPeriod'];

        if (!is_string($appKey) || empty($appKey)
            || !is_string($appSecret) || empty($appSecret)
            || !is_string($consumerKey) || empty($consumerKey)
            || !is_string($apiEndpoint) || empty($apiEndpoint)
            || !is_string($ovhSubsidiary) || empty($ovhSubsidiary)
            || !is_string($pricingMode) || empty($pricingMode)
        ) {
            throw new BadRequestHttpException('Bad authData schema');
        }

        if (true !== $acceptConditions
            || true !== $ownerLegalAge
            || true !== $waiveRetractationPeriod) {
            throw new HttpException(451, 'The user has not given explicit consent');
        }

        return [
            'appKey' => $appKey,
            'appSecret' => $appSecret,
            'apiEndpoint' => $apiEndpoint,
            'consumerKey' => $consumerKey,
            'ovhSubsidiary' => $ovhSubsidiary,
            'pricingMode' => $pricingMode,
            'acceptConditions' => $acceptConditions,
            'ownerLegalAge' => $ownerLegalAge,
            'waiveRetractationPeriod' => $waiveRetractationPeriod,
        ];
    }

    public function assertAuthentication(): void
    {
        $conn = new Api(
            $this->authData['appKey'],
            $this->authData['appSecret'],
            $this->authData['apiEndpoint'],
            $this->authData['consumerKey'],
        );

        try {
            $res = $conn->get('/auth/currentCredential');
            if (null !== $res['expiration'] && new \DateTime($res['expiration']) < new \DateTime()) {
                throw new BadRequestHttpException('These credentials have expired');
            }

            $status = $res['status'];
            if ('validated' !== $status) {
                throw new BadRequestHttpException("The status of these credentials is not valid ($status)");
            }
        } catch (ClientException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        foreach (self::REQUIRED_ROUTES as $requiredRoute) {
            $ok = false;

            foreach ($res['rules'] as $allowedRoute) {
                if (
                    $requiredRoute['method'] === $allowedRoute['method']
                    && fnmatch($allowedRoute['path'], $requiredRoute['path'])
                ) {
                    $ok = true;
                }
            }

            if (!$ok) {
                throw new BadRequestHttpException('This Connector does not have enough permissions on the Provider API. Please recreate this Connector.');
            }
        }
    }

    /**
     * @throws InvalidParameterException
     * @throws \JsonException
     * @throws \Exception
     */
    protected function getSupportedTldList(): array
    {
        $conn = new Api(
            $this->authData['appKey'],
            $this->authData['appSecret'],
            $this->authData['apiEndpoint'],
            $this->authData['consumerKey']
        );

        return $conn->get('/domain/extensions', [
            'ovhSubsidiary' => $this->authData['ovhSubsidiary'],
        ]);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getCachedTldList(): CacheItemInterface
    {
        return $this->cacheItemPool->getItem('app.provider.ovh.supported-tld');
    }
}
