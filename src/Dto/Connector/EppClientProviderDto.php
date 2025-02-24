<?php

namespace App\Dto\Connector;

use Symfony\Component\Validator\Constraints as Assert;

final class EppClientProviderDto extends DefaultProviderDto
{
    #[Assert\NotBlank]
    public string $version;

    #[Assert\NotBlank]
    #[Assert\Language]
    public string $language;

    #[Assert\NotBlank]
    #[Assert\Url(protocols: ['ssl', 'tls', 'http', 'https'], requireTld: true)]
    public string $hostname;

    #[Assert\NotBlank]
    public int $port;

    #[Assert\NotBlank]
    public EppClientProviderAuthDto $auth;

    #[Assert\NotBlank]
    public EppClientProviderDomainDto $domain;

    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Type('string'),
    ])]
    public array $xPathURI = [];

    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Type('string'),
    ])]
    public array $extURI = [];

    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Type('string'),
    ])]
    public array $objURI = [];

    public ?string $file_certificate_pem = null;

    public ?string $file_certificate_key = null;
}
