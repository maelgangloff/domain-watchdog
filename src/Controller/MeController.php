<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\SerializerInterface;

class MeController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly RateLimiterFactory $rdapRequestsLimiter,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->getUser();
        $limiter = $this->rdapRequestsLimiter->create($user->getUserIdentifier());
        $limit = $limiter->consume(0);

        $data = $this->serializer->serialize($user, 'json', ['groups' => 'user:list']);

        return new JsonResponse($data, Response::HTTP_OK, [
            'eu.domainwatchdog.ratelimiter.rdap.remaining' => $limit->getRemainingTokens(),
            'eu.domainwatchdog.ratelimiter.rdap.limit' => $limit->getLimit(),
        ], true);
    }
}
