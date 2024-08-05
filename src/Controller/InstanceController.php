<?php

namespace App\Controller;

use App\Entity\Instance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InstanceController extends AbstractController
{
    public function __invoke(): Instance
    {
        $instance = new Instance();

        $instance
            ->setLimitedFeatures($this->getParameter('limited_features') ?? false)
            ->setOauthEnabled($this->getParameter('oauth_enabled') ?? false)
            ->setRegisterEnabled($this->getParameter('registration_enabled') ?? false);

        return $instance;
    }
}
