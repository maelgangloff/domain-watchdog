<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private KernelInterface $kernel,
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private EmailVerifier $emailVerifier,
        private string $mailerSenderEmail,
        private string $mailerSenderName,
        private RateLimiterFactory $userRegisterLimiter,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $userPasswordHasher,
        private RequestStack $requestStack,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = $this->requestStack->getCurrentRequest();
        if (false === $this->parameterBag->get('registration_enabled')) {
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
        $violations = $this->validator->validate($user);

        if ($violations->count() > 0) {
            throw new BadRequestHttpException($violations->get(0));
        }

        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            )
        )->setCreatedAt(new \DateTimeImmutable());

        if (false === (bool) $this->parameterBag->get('registration_verify_email')) {
            $user->setVerifiedAt($user->getCreatedAt());
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
}
