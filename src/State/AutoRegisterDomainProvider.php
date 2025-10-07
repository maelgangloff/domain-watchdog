<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Exception\DomainNotFoundException;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

readonly class AutoRegisterDomainProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        private RDAPService $RDAPService,
        private EntityManagerInterface $entityManager,
        private KernelInterface $kernel,
        private ParameterBagInterface $parameterBag,
        private RateLimiterFactory $rdapRequestsLimiter, private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $domain = $this->itemProvider->provide($operation, $uriVariables, $context);

        if (!is_null($domain)) {
            return $domain;
        }

        if (false === $this->kernel->isDebug() && true === $this->parameterBag->get('limited_features')) {
            $limiter = $this->rdapRequestsLimiter->create($this->security->getUser()->getUserIdentifier());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
            }
        }

        $ldhName = RDAPService::convertToIdn($uriVariables['ldhName']);

        try {
            $domain = $this->RDAPService->registerDomain($ldhName);
        } catch (DomainNotFoundException) {
            $domain = (new Domain())
                ->setLdhName($ldhName)
                ->setTld($this->RDAPService->getTld($ldhName))
                ->setDelegationSigned(false)
                ->setDeleted(true);

            $this->entityManager->persist($domain);
            $this->entityManager->flush();
        }

        return $domain;
    }
}
