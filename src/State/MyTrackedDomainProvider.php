<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Entity\User;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Symfony\Bundle\SecurityBundle\Security;

readonly class MyTrackedDomainProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private DomainRepository $domainRepository,
        private RDAPService $RDAPService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $domains = $this->domainRepository->getMyTrackedDomains($user);
        foreach ($domains as $domain) {
            $domain->setExpiresInDays($this->RDAPService->getExpiresInDays($domain));
        }

        usort($domains, fn (Domain $d1, Domain $d2) => $d1->getExpiresInDays() - $d2->getExpiresInDays());

        return $domains;
    }
}
