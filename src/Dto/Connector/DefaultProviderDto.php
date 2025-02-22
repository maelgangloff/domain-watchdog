<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

class DefaultProviderDto
{
    #[Assert\IsTrue]
    public bool $ownerLegalAge;

    #[Assert\IsTrue]
    public bool $acceptConditions;

    #[Assert\IsTrue]
    public bool $waiveRetractationPeriod;
}
