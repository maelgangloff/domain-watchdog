<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\DomainStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainStatus>
 */
class DomainStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainStatus::class);
    }

    public function findNewDomainStatus(Domain $domain, \DateTimeImmutable $updatedAt): ?DomainStatus
    {
        return $this->createQueryBuilder('ds')
            ->select()
            ->where('ds.domain = :domain')
            ->andWhere('ds.date = :date')
            ->orderBy('ds.createdAt', 'DESC')
            ->setParameter('domain', $domain)
            ->setParameter('date', $updatedAt)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLastDomainStatus(Domain $domain): ?DomainStatus
    {
        return $this->createQueryBuilder('ds')
            ->select()
            ->where('ds.domain = :domain')
            ->setParameter('domain', $domain)
            ->orderBy('ds.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return DomainStatus[] Returns an array of DomainStatus objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DomainStatus
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
