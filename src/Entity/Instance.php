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
            shortName: 'Configuration',
            read: false,
        ),
    ]
)]
class Instance
{
    private ?bool $oauthEnabled = null;

    private ?bool $registerEnabled = null;

    private ?bool $limitedFeatures = null;

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
}
