<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\ProcessDomainTrigger;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DomainRefreshController extends AbstractController
{
    public function __construct(private readonly DomainRepository $domainRepository,
        private readonly RDAPService $RDAPService,
        private readonly RateLimiterFactory $authenticatedApiLimiter,
        private readonly MessageBusInterface $bus)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws HttpExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function __invoke(string $ldhName, KernelInterface $kernel): ?Domain
    {
        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $ldhName]);

        // If the domain name exists in the database, recently updated and not important, we return the stored Domain
        if (null !== $domain
            && !$domain->getDeleted()
            && ($domain->getUpdatedAt()->diff(new \DateTimeImmutable('now'))->days < 7)
            && !$this->RDAPService::isToBeWatchClosely($domain, $domain->getUpdatedAt())
        ) {
            return $domain;
        }

        if (false === $kernel->isDebug()) {
            $limiter = $this->authenticatedApiLimiter->create($this->getUser()->getUserIdentifier());
            if (false === $limiter->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
        }

        $updatedAt = null === $domain->getUpdatedAt() ? new \DateTimeImmutable('now') : $domain->getUpdatedAt();
        $domain = $this->RDAPService->registerDomain($ldhName);

        /** @var WatchList $watchList */
        foreach ($domain->getWatchLists()->getIterator() as $watchList) {
            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }

        return $domain;
    }
}
