<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class JWTAuthenticator implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        protected JWTTokenManagerInterface $jwtManager,
        protected EventDispatcherInterface $dispatcher,
        protected KernelInterface $kernel
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        return $this->handleAuthenticationSuccess($token->getUser());
    }

    public function handleAuthenticationSuccess(UserInterface $user, $jwt = null): Response
    {
        if (($user instanceof User) && !$user->isVerified()) {
            throw new AccessDeniedHttpException('You have not yet validated your email address.');
        }

        if (null === $jwt) {
            $jwt = $this->jwtManager->create($user);
        }

        $jwtCookies = [
            new Cookie(
                'BEARER',
                $jwt,
                time() + 604800, // expiration
                '/',
                null,
                !$this->kernel->isDebug(),
                true,
                false,
                'strict'
            ),
        ];

        $response = new JWTAuthenticationSuccessResponse($jwt, [], $jwtCookies);
        $event = new AuthenticationSuccessEvent(['token' => $jwt], $user, $response);

        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);
        $responseData = $event->getData();

        if ($responseData) {
            $response->setData($responseData);
        } else {
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
        }

        return $response;
    }
}
