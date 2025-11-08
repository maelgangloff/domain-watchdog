<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\DomainRepository;
use App\Service\RDAPService;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class FindDomainCollectionFromEntityProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private DomainRepository $domainRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        $registrant = trim((string) $request->get('registrant'));

        $forbidden = [
            'redacted',
            'privacy',
            'registration private',
            'domain administrator',
            'registry super user account',
            'ano nymous',
            'by proxy',
        ];

        foreach ($forbidden as $word) {
            if (str_contains(strtolower($registrant), $word)) {
                throw new BadRequestHttpException('Forbidden search term');
            }
        }

        return $this->domainRepository->createQueryBuilder('d')
            ->select('DISTINCT d')
            ->join('d.domainEntities', 'de', Join::WITH, 'de.deletedAt IS NULL AND JSONB_CONTAINS(de.roles, :role) = true')
            ->join(
                'de.entity',
                'e',
                Join::WITH,
                'e.tld IS NOT NULL AND e.handle NOT IN (:blacklist) AND (e.jCardOrg = UPPER(:registrant) OR e.jCardFn = UPPER(:registrant))'
            )
            ->setParameter('registrant', $registrant)
            ->setParameter('blacklist', RDAPService::ENTITY_HANDLE_BLACKLIST)
            ->setParameter('role', '"registrant"')
            ->getQuery()->getResult();
    }
}
