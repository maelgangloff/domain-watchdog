<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    #[Route(path: "/", name: "index")]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route(path: "/login/oauth", name: "oauth_connect")]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('oauth')->redirect();
    }
}
