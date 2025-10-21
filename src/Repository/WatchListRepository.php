<?php

namespace App\Repository;

use App\Entity\WatchList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WatchList>
 */
class WatchListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchList::class);
    }

    public function getTrackedDomainCount()
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(DISTINCT d.ldhName)')
            ->join('w.domains', 'd')
            ->where('d.deleted = FALSE')
            ->getQuery()->getSingleScalarResult();
    }

    //    /**
    //     * @return WatchList[] Returns an array of WatchList objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WatchList
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
