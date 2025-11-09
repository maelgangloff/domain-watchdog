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
        $administrative = trim((string) $request->get('administrative'));

        if ('' === $registrant && '' === $administrative) {
            throw new BadRequestHttpException('Either "registrant" or "administrative" must be provided');
        }

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
            if (str_contains(strtolower($registrant.' '.$administrative), $word)) {
                throw new BadRequestHttpException('Forbidden search term');
            }
        }

        $qb = $this->domainRepository->createQueryBuilder('d')
            ->select('DISTINCT d')
            ->setParameter('blacklist', RDAPService::ENTITY_HANDLE_BLACKLIST);

        $orX = $qb->expr()->orX();

        if ($registrant) {
            $qb
                ->leftJoin(
                    'd.domainEntities',
                    'der',
                    Join::WITH,
                    'der.deletedAt IS NULL AND JSONB_CONTAINS(der.roles, \'"registrant"\') = true'
                )
                ->leftJoin(
                    'der.entity',
                    'er',
                    Join::WITH,
                    'er.handle NOT IN (:blacklist) AND (er.jCardOrg = UPPER(:registrant) OR er.jCardFn = UPPER(:registrant))'
                )
                ->setParameter('registrant', $registrant);

            $orX->add('er.id IS NOT NULL');
        }

        if ($administrative) {
            $qb
                ->leftJoin(
                    'd.domainEntities',
                    'dea',
                    Join::WITH,
                    'dea.deletedAt IS NULL AND JSONB_CONTAINS(dea.roles, \'"administrative"\') = true'
                )
                ->leftJoin(
                    'dea.entity',
                    'ea',
                    Join::WITH,
                    'ea.handle NOT IN (:blacklist) AND (ea.jCardOrg = UPPER(:administrative) OR ea.jCardFn = UPPER(:administrative))'
                )
                ->setParameter('administrative', $administrative);

            $orX->add('ea.id IS NOT NULL');
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX);
        }

        return $qb->getQuery()->getResult();
    }
}
