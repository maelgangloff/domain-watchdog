<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class EppClientProviderFilesDto
{
    #[Assert\NotBlank]
    public string $pem;

    #[Assert\NotBlank]
    public string $key;
}
