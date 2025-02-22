<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class GandiProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $token;

    public ?string $sharingId = null;
}
