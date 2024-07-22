<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route(path: "/login/oauth/token", name: "oauth_connect_token")]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function loginOAuthToken(UserInterface $user, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
    }
}
