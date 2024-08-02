<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class HomeController extends AbstractController
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route(path: '/login/oauth', name: 'oauth_connect')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('oauth')->redirect();
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(Security $security): ?RedirectResponse
    {
        $response = new RedirectResponse($this->router->generate('index'));
        $response->headers->clearCookie('BEARER');
        if ($security->isGranted('IS_AUTHENTICATED')) {
            $security->logout(false);
        }

        return $response;
    }
}
