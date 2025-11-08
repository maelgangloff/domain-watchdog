<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\DomainRepository;
use App\Repository\EntityRepository;
use App\Service\RDAPService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

readonly class FindDomainCollectionFromEntityProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityRepository $entityRepository,
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
                throw new HttpException(403, 'Forbidden search term');
            }
        }

        $entities = $this->entityRepository->createQueryBuilder('e')
            ->where('e.tld IS NOT NULL')
            ->andWhere('e.handle NOT IN (:blacklist)')
            ->andWhere('UPPER(e.jCardOrg) = UPPER(:registrant) OR UPPER(e.jCardFn) = UPPER(:registrant)')
            ->setParameter('registrant', $registrant)
            ->setParameter('blacklist', RDAPService::ENTITY_HANDLE_BLACKLIST)
            ->getQuery()
            ->getResult();

        if (empty($entities)) {
            return [];
        }

        return $this->domainRepository->createQueryBuilder('d')
            ->select('DISTINCT d')
            ->join('d.domainEntities', 'de')
            ->where('de.entity IN (:entityIds)')
            ->andWhere('JSONB_CONTAINS(de.roles, :role) = true')
            ->andWhere('de.deletedAt IS NULL')
            ->setParameter('entityIds', array_map(fn ($e) => $e->getId(), $entities))
            ->setParameter('role', '"registrant"')
            ->getQuery()->getResult();
    }
}
