<?php

namespace App\Security;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class OAuthProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public function __construct(private readonly array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->options['baseAuthorizationUrl'];
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->options['baseAccessTokenUrl'];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->options['resourceOwnerDetailsUrl'];
    }

    protected function getDefaultScopes(): array
    {
        return explode(',', $this->options['scope']);
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException($data['error'] ?? 'Unknown error', $response->getStatusCode(), $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new OAuthResourceOwner($response);
    }
}
