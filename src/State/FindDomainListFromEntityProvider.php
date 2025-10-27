<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Domain;
use App\Service\RDAPService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class FindDomainListFromEntityProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        $rsm = (new ResultSetMapping())
            ->addScalarResult('handles', 'handles')
            ->addScalarResult('domain_ids', 'domain_ids')
            ->addScalarResult('registrant', 'registrant');

        $handleBlacklist = join(',', array_map(fn (string $s) => "'$s'", RDAPService::ENTITY_HANDLE_BLACKLIST));

        $sql = <<<SQL
SELECT
    array_agg(DISTINCT sub.handle || '.' || sub.tld_id) AS handles,
    array_agg(DISTINCT de.domain_id) AS domain_ids,
    COUNT(de.domain_id) AS cnt,
    COALESCE(org, fn) AS registrant
FROM (
    SELECT
        e.handle AS handle,
        e.id,
        e.tld_id,
        jsonb_path_query_first(
            e.j_card,
            '$[1] ? (@[0] == "fn")[3]'
        ) #>> '{}' AS fn,
        jsonb_path_query_first(
            e.j_card,
            '$[1] ? (@[0] == "org")[3]'
        ) #>> '{}' AS org
    FROM entity e
) sub
JOIN domain_entity de ON de.entity_uid = sub.id
WHERE LOWER(org||fn) NOT LIKE '%redacted%'
  AND LOWER(org||fn) NOT LIKE '%privacy%'
  AND LOWER(org||fn) NOT LIKE '%registration private%'
  AND LOWER(org||fn) NOT LIKE '%domain administrator%'
  AND LOWER(org||fn) NOT LIKE '%registry super user account%'
  AND LOWER(org||fn) NOT LIKE '%ano nymous%'
  AND LOWER(org||fn) NOT LIKE '%by proxy%'
  AND handle NOT IN ($handleBlacklist)
  AND de.roles @> '["registrant"]'
  AND sub.tld_id IS NOT NULL
  AND (LOWER(org) = LOWER(:var) OR LOWER(fn) = LOWER(:var))
GROUP BY COALESCE(org, fn)
HAVING COALESCE(org, fn) != '' AND COALESCE(org, fn) != '' IS NOT NULL
ORDER BY cnt DESC;
SQL;
        $query = $this->em->createNativeQuery($sql, $rsm);
        $query->setParameter('var', trim($request->get('registrant')));

        $result = $query->getOneOrNullResult();

        if (!$result) {
            return null;
        }

        $domainList = array_filter(explode(',', trim($result['domain_ids'], '{}')));

        if (empty($domainList)) {
            return [];
        }

        return $this->em->getRepository(Domain::class)
            ->createQueryBuilder('d')
            ->where('d.ldhName IN (:list)')
            ->setParameter('list', $domainList)
            ->getQuery()
            ->getResult();
    }
}
