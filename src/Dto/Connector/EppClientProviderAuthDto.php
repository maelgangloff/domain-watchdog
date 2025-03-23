<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class EppClientProviderAuthDto
{
    #[Assert\NotBlank]
    public string $username;

    #[Assert\NotBlank]
    public string $password;

    #[Assert\NotBlank]
    public EppClientProviderAuthSSLDto $ssl;
}
