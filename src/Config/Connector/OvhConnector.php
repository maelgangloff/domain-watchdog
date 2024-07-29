<?php

namespace App\Config\Connector;

use App\Entity\Domain;
use Exception;
use Ovh\Api;

readonly class OvhConnector implements ConnectorInterface
{

    public function __construct(private array $authData)
    {

    }

    /**
     * Order a domain name with the OVH API
     * @throws Exception
     */
    public function orderDomain(Domain $domain,
                                bool   $acceptConditions,
                                bool   $ownerLegalAge,
                                bool   $waiveRetractationPeriod,
                                bool   $dryRyn = false
    ): void
    {
        if (!$domain->getDeleted()) throw new Exception('The domain name still appears in the WHOIS database');

        $ldhName = $domain->getLdhName();
        if (!$ldhName) throw new Exception("Domain name cannot be null");

        $authData = self::verifyAuthData($this->authData);

        $appKey = $authData['appKey'];
        $appSecret = $authData['appSecret'];
        $apiEndpoint = $authData['apiEndpoint'];
        $consumerKey = $authData['consumerKey'];
        $ovhSubsidiary = $authData['ovhSubsidiary'];
        $pricingMode = $authData['pricingMode'];

        $conn = new Api(
            $appKey,
            $appSecret,
            $apiEndpoint,
            $consumerKey
        );

        $cart = $conn->post('/order/cart', [
            "ovhSubsidiary" => $ovhSubsidiary,
            "description" => "Domain Watchdog"
        ]);
        $cartId = $cart['cartId'];

        $offers = $conn->get("/order/cart/{$cartId}/domain", [
            "domain" => $ldhName
        ]);
        $offer = array_filter($offers, fn($offer) => $offer['action'] === 'create' &&
            $offer['orderable'] === true &&
            $offer['pricingMode'] === $pricingMode
        );
        if (empty($offer)) throw new Exception('Cannot buy this domain name');

        $item = $conn->post("/order/cart/{$cartId}/domain", [
            "domain" => $ldhName,
            "duration" => "P1Y"
        ]);
        $itemId = $item['itemId'];

        //$conn->get("/order/cart/{$cartId}/summary");
        $conn->post("/order/cart/{$cartId}/assign");
        $conn->get("/order/cart/{$cartId}/item/{$itemId}/requiredConfiguration");

        $configuration = [
            "ACCEPT_CONDITIONS" => $acceptConditions,
            "OWNER_LEGAL_AGE" => $ownerLegalAge
        ];

        foreach ($configuration as $label => $value) {
            $conn->post("/order/cart/{$cartId}/item/{$itemId}/configuration", [
                "cartId" => $cartId,
                "itemId" => $itemId,
                "label" => $label,
                "value" => $value
            ]);
        }
        $conn->get("/order/cart/{$cartId}/checkout");

        if ($dryRyn) return;
        $conn->post("/order/cart/{$cartId}/checkout", [
            "autoPayWithPreferredPaymentMethod" => true,
            "waiveRetractationPeriod" => $waiveRetractationPeriod
        ]);
    }

    /**
     * @throws Exception
     */
    public static function verifyAuthData(array $authData): array
    {
        $appKey = $authData['appKey'];
        $appSecret = $authData['appSecret'];
        $apiEndpoint = $authData['apiEndpoint'];
        $consumerKey = $authData['consumerKey'];
        $ovhSubsidiary = $authData['ovhSubsidiary'];
        $pricingMode = $authData['pricingMode'];

        if (!is_string($appKey) || empty($appKey) ||
            !is_string($appSecret) || empty($appSecret) ||
            !is_string($consumerKey) || empty($consumerKey) ||
            !is_string($apiEndpoint) || empty($apiEndpoint) ||
            !is_string($ovhSubsidiary) || empty($ovhSubsidiary) ||
            !is_string($pricingMode) || empty($pricingMode)
        ) throw new Exception("Bad data schema.");

        return [
            "appKey" => $appKey,
            "appSecret" => $appSecret,
            "apiEndpoint" => $apiEndpoint,
            "consumerKey" => $consumerKey,
            "ovhSubsidiary" => $ovhSubsidiary,
            "pricingMode" => $pricingMode
        ];
    }
}