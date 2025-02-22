<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class NameComProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $username;

    #[Assert\NotBlank]
    public string $token;
}
