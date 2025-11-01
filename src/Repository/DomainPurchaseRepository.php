<?php

namespace App\Repository;

use App\Entity\DomainPurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainPurchase>
 */
class DomainPurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainPurchase::class);
    }

    public function countSuccessDomainPurchase(): int
    {
        return $this->createQueryBuilder('dp')
            ->select('COUNT(*)')
            ->where('dp.domainOrderedAt != NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function countFailDomainPurchase(): int
    {
        return $this->createQueryBuilder('dp')
            ->select('COUNT(*)')
            ->where('dp.domainOrderedAt == NULL')
            ->getQuery()->getSingleScalarResult();
    }
}
