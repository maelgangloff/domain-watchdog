<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\DomainEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainEntity>
 */
class DomainEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainEntity::class);
    }

    public function setDomainEntityAsDeleted(Domain $domain)
    {
        return $this->createQueryBuilder('de')
            ->update()
            ->set('de.deletedAt', ':now')
            ->where('de.domain = :domain')
            ->andWhere('de.deletedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('domain', $domain)
            ->getQuery()->execute();
    }

    //    /**
    //     * @return DomainEntity[] Returns an array of DomainEntity objects
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

    //    public function findOneBySomeField($value): ?DomainEntity
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
