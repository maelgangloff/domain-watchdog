<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class OvhProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $appKey;

    #[Assert\NotBlank]
    public string $appSecret;

    #[Assert\NotBlank]
    public string $apiEndpoint;

    #[Assert\NotBlank]
    public string $consumerKey;

    #[Assert\NotBlank]
    #[Assert\Choice(['create-default', 'create-premium'])]
    public string $pricingMode;

    #[Assert\NotBlank]
    public string $ovhSubsidiary;
}
