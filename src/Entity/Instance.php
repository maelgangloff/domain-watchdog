<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\InstanceController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/config',
            controller: InstanceController::class,
            openapiContext: [
                'summary' => 'Public configuration of the server',
                'description' => 'This endpoint allows you to retrieve the public configuration of the Domain Watchdog API server. For example, you can retrieve the user authentication configuration.',
            ],
            shortName: 'Configuration',
            read: false
        ),
    ]
)]
class Instance
{
    private ?bool $oauthEnabled = null;

    private ?bool $registerEnabled = null;

    private ?bool $limitedFeatures = null;

    private ?bool $ssoAutoRedirect = null;

    private ?bool $publicRdapLookupEnabled = null;

    public function isSsoLogin(): ?bool
    {
        return $this->oauthEnabled;
    }

    public function setOauthEnabled(bool $oauthEnabled): static
    {
        $this->oauthEnabled = $oauthEnabled;

        return $this;
    }

    public function isLimitedFeatures(): ?bool
    {
        return $this->limitedFeatures;
    }

    public function setLimitedFeatures(bool $limitedFeatures): static
    {
        $this->limitedFeatures = $limitedFeatures;

        return $this;
    }

    public function getRegisterEnabled(): ?bool
    {
        return $this->registerEnabled;
    }

    public function setRegisterEnabled(?bool $registerEnabled): static
    {
        $this->registerEnabled = $registerEnabled;

        return $this;
    }

    public function getSsoAutoRedirect(): ?bool
    {
        return $this->ssoAutoRedirect;
    }

    public function setSsoAutoRedirect(?bool $ssoAutoRedirect): static
    {
        $this->ssoAutoRedirect = $ssoAutoRedirect;

        return $this;
    }

    public function getPublicRdapLookupEnabled(): ?bool
    {
        return $this->publicRdapLookupEnabled;
    }

    public function setPublicRdapLookupEnabled(?bool $publicRdapLookupEnabled): static
    {
        $this->publicRdapLookupEnabled = $publicRdapLookupEnabled;

        return $this;
    }
}
