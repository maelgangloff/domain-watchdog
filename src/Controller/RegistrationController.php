<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly string $mailerSenderEmail,
        private readonly string $mailerSenderName,
        private readonly RateLimiterFactory $userRegisterLimiter,
        private readonly EntityManagerInterface $em,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(
        path: '/api/register',
        name: 'user_register',
        defaults: [
            '_api_resource_class' => User::class,
            '_api_operation_name' => 'user_register',
        ],
        methods: ['POST']
    )]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (false === $this->getParameter('registration_enabled')) {
            throw new UnauthorizedHttpException('', 'Registration is disabled on this instance');
        }

        if (false === $this->kernel->isDebug()) {
            $limiter = $this->userRegisterLimiter->create($request->getClientIp());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json', ['groups' => 'user:register']);
        if (null === $user->getEmail() || null === $user->getPassword()) {
            throw new BadRequestHttpException('Bad request');
        }

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );

        if (false === (bool) $this->getParameter('registration_verify_email')) {
            $user->setVerified(true);
        } else {
            $email = $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address($this->mailerSenderEmail, $this->mailerSenderName))
                    ->to($user->getEmail())
                    ->locale('en')
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('emails/success/confirmation_email.html.twig')
            );

            $signedUrl = (string) $email->getContext()['signedUrl'];
            $this->logger->notice('The validation link for this user is generated', [
                'username' => $user->getUserIdentifier(),
                'signedUrl' => $signedUrl,
            ]);
        }

        $this->em->persist($user);
        $this->em->flush();

        $this->logger->info('New user has registered', [
            'username' => $user->getUserIdentifier(),
        ]);

        return new Response(null, 201);
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
