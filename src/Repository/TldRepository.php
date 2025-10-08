<?php

namespace App\Repository;

use App\Entity\Tld;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tld>
 */
class TldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tld::class);
    }

    /**
     * @return Tld[] Returns an array of deleted Tld
     */
    public function findDeleted(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.deletedAt IS NOT NULL')
            ->orderBy('t.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Tld[] Returns an array of Tld objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tld
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
