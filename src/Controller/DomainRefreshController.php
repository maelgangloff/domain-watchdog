<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DomainRefreshController extends AbstractController
{

    public function __construct(private readonly DomainRepository   $domainRepository,
                                private readonly RDAPService        $RDAPService,
                                private readonly RateLimiterFactory $authenticatedApiLimiter)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(string $ldhName,): ?Domain
    {
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(["ldhName" => $ldhName]);
        if ($domain === null ||
            $domain->getUpdatedAt()->diff(new DateTimeImmutable('now'))->days >= 7) {

            $limiter = $this->authenticatedApiLimiter->create($this->getUser()->getUserIdentifier());
            if (false === $limiter->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }

            $domain = $this->RDAPService->registerDomain($ldhName);
        }
        return $domain;
    }
}