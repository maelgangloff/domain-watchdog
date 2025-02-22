<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class EppClientProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $version;

    #[Assert\NotBlank]
    public string $language;

    #[Assert\NotBlank]
    public string $hostname;

    #[Assert\NotBlank]
    public int $port;

    #[Assert\NotBlank]
    public EppClientProviderAuthDto $auth;

    #[Assert\NotBlank]
    public EppClientProviderDomainDto $domain;

    public array $xPathURI = [];

    public array $extURI = [];

    public array $objURI = [];

    #[Assert\NotBlank]
    public string $certificate_pem;

    #[Assert\NotBlank]
    public string $certificate_key;

    public ?EppClientProviderFilesDto $files;
}
