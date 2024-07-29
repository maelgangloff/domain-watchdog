<?php

namespace App\Entity;

use App\Config\ConnectorInterface;
use App\Repository\OVHConnectorRepository;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Ovh\Api;

#[Entity(repositoryClass: OVHConnectorRepository::class)]
class OVHConnector extends Connector implements ConnectorInterface
{

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
        if (!$domain->getDeleted()) throw new Exception('The domain name still appears in the WHOIS database.');

        $ldhName = $domain->getLdhName();
        if (!$ldhName) throw new Exception("Domain name cannot be null.");

        $authData = $this->getAuthData();

        $appKey = $authData['appKey'];
        $appSecret = $authData['appSecret'];
        $apiEndpoint = $authData['apiEndpoint'];
        $consumerKey = $authData['consumerKey'];
        $ovhSubsidiary = $authData['ovhSubsidiary'];
        $pricingMode = $authData['pricingMode'];

        if (!$appKey ||
            !$appSecret ||
            !$apiEndpoint ||
            !$consumerKey ||
            !$ovhSubsidiary ||
            !$pricingMode
        ) throw new Exception("Auth data cannot be null.");

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
            $offers['pricingMode'] === $pricingMode
        );
        if (empty($offer)) throw new Exception('Cannot buy this domain name.');

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
}