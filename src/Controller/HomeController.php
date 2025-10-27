<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route(path: '/login/oauth', name: 'oauth_connect')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        if ($this->parameterBag->get('oauth_enabled')) {
            return $clientRegistry->getClient('oauth')->redirect([], []);
        }
        throw new NotFoundHttpException();
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
