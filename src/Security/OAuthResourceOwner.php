<?php

namespace App\Security;

namespace App\Security;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class OAuthResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    public array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId(): string
    {
        return $this->response['sub'];
    }

    public function toArray(): array
    {
        return $this->response;
    }

    public function getEmail(): string
    {
        return $this->response['email'];
    }

    public function getName(): string
    {
        return $this->response['name'];
    }
}
