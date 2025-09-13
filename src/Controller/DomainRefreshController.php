<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DomainRefreshController extends AbstractController
{
    public function __construct(
        private readonly RDAPService $RDAPService,
        private readonly RateLimiterFactory $rdapRequestsLimiter,
        private readonly LoggerInterface $logger,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ExceptionInterface
     * @throws \Exception
     * @throws HttpExceptionInterface
     * @throws \Throwable
     */
    public function __invoke(string $ldhName, Request $request): Domain
    {
        $idnDomain = RDAPService::convertToIdn($ldhName);
        $userId = $this->getUser()->getUserIdentifier();

        $this->logger->info('User {username} wants to update the domain name {idnDomain}.', [
            'username' => $userId,
            'idnDomain' => $idnDomain,
        ]);

        if (false === $this->kernel->isDebug() && true === $this->getParameter('limited_features')) {
            $limiter = $this->rdapRequestsLimiter->create($userId);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        return $this->RDAPService->updateDomain($idnDomain, $request->get('forced', false));
    }
}
