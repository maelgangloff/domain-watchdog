<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\ProcessDomainTrigger;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
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
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger
    ) {
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
        $idnDomain = strtolower(idn_to_ascii($ldhName));
        $userId = $this->getUser()->getUserIdentifier();

        $this->logger->info('User {username} wants to update the domain name {idnDomain}.', [
            'username' => $userId,
            'idnDomain' => $idnDomain,
        ]);

        /** @var ?Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);

        // If the domain name exists in the database, recently updated and not important, we return the stored Domain
        if (null !== $domain
            && !$domain->getDeleted()
            && ($domain->getUpdatedAt()->diff(new \DateTimeImmutable('now'))->days < 7)
            && !$this->RDAPService::isToBeWatchClosely($domain, $domain->getUpdatedAt())
        ) {
            $this->logger->info('It is not necessary to update the information of the domain name {idnDomain} with the RDAP protocol.', [
                'idnDomain' => $idnDomain,
            ]);

            return $domain;
        }

        if (false === $kernel->isDebug()) {
            $limiter = $this->authenticatedApiLimiter->create($userId);
            if (false === $limiter->consume()->isAccepted()) {
                $this->logger->warning('User {username} was rate limited by the API.', [
                    'username' => $this->getUser()->getUserIdentifier(),
                ]);
                throw new TooManyRequestsHttpException();
            }
        }

        $updatedAt = null === $domain ? new \DateTimeImmutable('now') : $domain->getUpdatedAt();
        $domain = $this->RDAPService->registerDomain($idnDomain);

        /** @var WatchList $watchList */
        foreach ($domain->getWatchLists()->getIterator() as $watchList) {
            $this->bus->dispatch(new ProcessDomainTrigger($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }

        return $domain;
    }
}
