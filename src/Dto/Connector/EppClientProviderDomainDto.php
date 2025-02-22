<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class EppClientProviderDomainDto
{
    #[Assert\NotBlank]
    public int $period;

    #[Assert\NotBlank]
    public string $unit;

    #[Assert\NotBlank]
    public string $registrant;

    #[Assert\NotBlank]
    public string $password;

    #[Assert\NotBlank]
    public array $contacts;
}
