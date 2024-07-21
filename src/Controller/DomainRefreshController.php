<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\ProcessDomainTrigger;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class DomainRefreshController extends AbstractController
{

    public function __construct(private readonly DomainRepository    $domainRepository,
                                private readonly RDAPService         $RDAPService,
                                private readonly RateLimiterFactory  $authenticatedApiLimiter,
                                private readonly MessageBusInterface $bus)
    {
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function __invoke(string $ldhName,): ?Domain
    {
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(["ldhName" => $ldhName]);
        if ($domain !== null && $domain->getUpdatedAt()->diff(new DateTimeImmutable('now'))->days < 7) return $domain;

        if ($this->container->getParameter('kernel.environment') !== 'dev') {
            $limiter = $this->authenticatedApiLimiter->create($this->getUser()->getUserIdentifier());
            if (false === $limiter->consume()->isAccepted()) throw new TooManyRequestsHttpException();
        }

        $updatedAt = $domain->getUpdatedAt();
        $domain = $this->RDAPService->registerDomain($ldhName);

        /** @var WatchList $watchList */
        foreach ($domain->getWatchLists()->getIterator() as $watchList) {
            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }

        return $domain;
    }
}