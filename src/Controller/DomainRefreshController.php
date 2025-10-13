<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\SendDomainEventNotif;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Random\Randomizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly RateLimiterFactory $rdapRequestsLimiter,
        private readonly MessageBusInterface $bus,
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

        $this->logger->info('User wants to update a domain name', [
            'username' => $userId,
            'ldhName' => $idnDomain,
        ]);

        /** @var ?Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);
        // If the domain name exists in the database, recently updated and not important, we return the stored Domain
        if (null !== $domain
            && !$domain->getDeleted()
            && !$domain->isToBeUpdated(true, true)
            && !$this->kernel->isDebug()
            && true !== filter_var($request->get('forced', false), FILTER_VALIDATE_BOOLEAN)
        ) {
            $this->logger->debug('It is not necessary to update the domain name', [
                'ldhName' => $idnDomain,
                'updatedAt' => $domain->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ]);

            return $domain;
        }

        if (false === $this->kernel->isDebug() && true === $this->getParameter('limited_features')) {
            $limiter = $this->rdapRequestsLimiter->create($userId);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        $updatedAt = null === $domain ? new \DateTimeImmutable('now') : $domain->getUpdatedAt();
        $domain = $this->RDAPService->registerDomain($idnDomain);

        $randomizer = new Randomizer();
        $watchLists = $randomizer->shuffleArray($domain->getWatchLists()->toArray());

        /** @var WatchList $watchList */
        foreach ($watchLists as $watchList) {
            $this->bus->dispatch(new SendDomainEventNotif($watchList->getToken(), $domain->getLdhName(), $updatedAt));
        }

        return $domain;
    }
}
