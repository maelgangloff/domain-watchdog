<?php

namespace App\Controller;

use App\Message\ProposeDomainMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ProposeDomainController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RateLimiterFactory $rdapRequestsLimiter,
        private readonly MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     */
    public function __invoke(string $ldhName, Request $request): Response
    {
        if (false === $this->kernel->isDebug()) {
            $limiter = $this->rdapRequestsLimiter->create($request->getClientIp());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        $this->bus->dispatch(new ProposeDomainMessage($ldhName));

        return new Response(null, 204);
    }
}
