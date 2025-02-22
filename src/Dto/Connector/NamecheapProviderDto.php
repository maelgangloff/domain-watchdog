<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class NamecheapProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $ApiUser;

    #[Assert\NotBlank]
    public string $ApiKey;
}
