<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

class MeController extends AbstractController
{
    public function __invoke(): UserInterface
    {
        return $this->getUser();
    }
}
