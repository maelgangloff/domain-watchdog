<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Entity\RdapResponse\DomainRdapResponse;
use App\Repository\DomainRepository;
use App\Service\RDAPService;

readonly class DomainRdapResponseProvider implements ProviderInterface
{
    public function __construct(
        private DomainRepository $domainRepository,
        private AutoRegisterDomainProvider $autoRegisterDomainProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $this->autoRegisterDomainProvider->provide($operation, $uriVariables, $context);

        $idnDomain = RDAPService::convertToIdn($uriVariables['ldhName']);

        /** @var Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);

        return new DomainRdapResponse($domain);
    }
}
