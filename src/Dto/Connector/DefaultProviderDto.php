<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

class DefaultProviderDto
{
    #[Assert\NotBlank]
    #[Assert\IsTrue]
    public bool $ownerLegalAge;

    #[Assert\NotBlank]
    #[Assert\IsTrue]
    public bool $acceptConditions;

    #[Assert\NotBlank]
    #[Assert\IsTrue]
    public bool $waiveRetractationPeriod;
}
