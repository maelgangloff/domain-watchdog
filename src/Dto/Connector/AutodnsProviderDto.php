<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class AutodnsProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $username;

    #[Assert\NotBlank]
    public string $password;

    #[Assert\IsTrue]
    public bool $ownerConfirm;

    public int $context = 4;

    #[Assert\NotBlank]
    public string $contactid;
}
