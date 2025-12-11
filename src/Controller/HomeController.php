<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EmailVerifier $emailVerifier,
        private readonly LoggerInterface $logger,
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

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('index');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('index');
        }

        $this->emailVerifier->handleEmailConfirmation($request, $user);

        $this->logger->info('User has validated his email address', [
            'username' => $user->getUserIdentifier(),
        ]);

        return $this->redirectToRoute('index');
    }
}
