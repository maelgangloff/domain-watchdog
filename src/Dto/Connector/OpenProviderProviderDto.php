<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class OpenProviderProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $token;

    #[Assert\NotBlank]
    public string $adminHandle;

    #[Assert\NotBlank]
    public string $billingHandle;

    #[Assert\NotBlank]
    public string $ownerHandle;

    #[Assert\NotBlank]
    public string $techHandle;

    public ?string $resellerHandle;

    #[Assert\NotBlank]
    public int $period = 1;

    public string $nsGroup;
}
