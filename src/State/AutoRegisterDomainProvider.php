<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Entity\WatchList;
use App\Message\SendDomainEventNotif;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Psr\Log\LoggerInterface;
use Random\Randomizer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

readonly class AutoRegisterDomainProvider implements ProviderInterface
{
    public function __construct(
        private RDAPService $RDAPService,
        private KernelInterface $kernel,
        private ParameterBagInterface $parameterBag,
        private RateLimiterFactory $rdapRequestsLimiter,
        private Security $security,
        private LoggerInterface $logger,
        private DomainRepository $domainRepository,
        private MessageBusInterface $bus,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $userId = $this->security->getUser()->getUserIdentifier();
        $idnDomain = RDAPService::convertToIdn($uriVariables['ldhName']);

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
            && true !== filter_var($context['request']->get('forced', false), FILTER_VALIDATE_BOOLEAN)
        ) {
            $this->logger->debug('It is not necessary to update the domain name', [
                'ldhName' => $idnDomain,
                'updatedAt' => $domain->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ]);

            return $domain;
        }

        if (false === $this->kernel->isDebug() && true === $this->parameterBag->get('limited_features')) {
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
