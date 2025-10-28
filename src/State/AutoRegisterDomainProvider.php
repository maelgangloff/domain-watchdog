<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Entity\Watchlist;
use App\Exception\DomainNotFoundException;
use App\Exception\MalformedDomainException;
use App\Exception\TldNotSupportedException;
use App\Exception\UnknownRdapServerException;
use App\Message\DetectDomainChange;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Random\Randomizer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class AutoRegisterDomainProvider implements ProviderInterface
{
    public function __construct(
        private RDAPService $RDAPService,
        private KernelInterface $kernel,
        private ParameterBagInterface $parameterBag,
        private RateLimiterFactory $userRdapRequestsLimiter,
        private Security $security,
        private LoggerInterface $logger,
        private DomainRepository $domainRepository,
        private MessageBusInterface $bus,
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DomainNotFoundException
     * @throws TldNotSupportedException
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws MalformedDomainException
     * @throws ServerExceptionInterface
     * @throws UnknownRdapServerException
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $fromWatchlist = array_key_exists('root_operation', $context) && Watchlist::class === $context['root_operation']?->getClass();

        $userId = $this->security->getUser()->getUserIdentifier();
        $idnDomain = RDAPService::convertToIdn($uriVariables['ldhName']);

        $this->logger->info('User wants to update a domain name', [
            'username' => $userId,
            'ldhName' => $idnDomain,
        ]);

        $request = $this->requestStack->getCurrentRequest();

        /** @var ?Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);
        // If the domain name exists in the database, recently updated and not important, we return the stored Domain
        if (null !== $domain
            && !$domain->getDeleted()
            && !$this->RDAPService->isToBeUpdated($domain, true, true)
            && ($request && !filter_var($request->get('forced', false), FILTER_VALIDATE_BOOLEAN))
        ) {
            $this->logger->debug('It is not necessary to update the domain name', [
                'ldhName' => $idnDomain,
                'updatedAt' => $domain->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ]);

            return $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
        }

        if (false === $this->kernel->isDebug() && true === $this->parameterBag->get('limited_features')) {
            $limiter = $this->userRdapRequestsLimiter->create($userId);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        $updatedAt = null === $domain ? new \DateTimeImmutable('now') : $domain->getUpdatedAt();

        try {
            $domain = $this->RDAPService->registerDomain($idnDomain);
        } catch (DomainNotFoundException $exception) {
            if (!$fromWatchlist) {
                throw $exception;
            }

            $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);
            if (null !== $domain) {
                return $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
            }

            $domain = (new Domain())
                ->setLdhName($idnDomain)
                ->setTld($this->RDAPService->getTld($idnDomain))
                ->setDelegationSigned(false)
                ->setDeleted(true);

            $this->em->persist($domain);
            $this->em->flush();

            return $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
        }

        $randomizer = new Randomizer();
        $watchlists = $randomizer->shuffleArray($domain->getWatchlists()->toArray());

        /** @var Watchlist $watchlist */
        foreach ($watchlists as $watchlist) {
            $this->bus->dispatch(new DetectDomainChange($watchlist->getToken(), $domain->getLdhName(), $updatedAt));
        }

        return $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
    }
}
