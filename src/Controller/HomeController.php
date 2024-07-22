<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractController
{

    #[Route(path: "/", name: "index")]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }


    #[Route(path: "/login/oauth", name: "connect_start")]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('oauth')->redirect();
    }

    #[Route(path: "/login/oauth/token", name: "login_oauth_token")]
    public function getToken(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}