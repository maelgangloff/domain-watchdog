<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\DomainEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainEvent>
 */
class DomainEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainEvent::class);
    }

    public function findLastExpirationDomainEvent(Domain $domain)
    {
        return $this->createQueryBuilder('de')
            ->select()
            ->where('de.domain = :domain')
            ->andWhere('de.action = \'expiration\'')
            ->andWhere('de.deleted = FALSE')
            ->orderBy('de.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setParameter('domain', $domain)
            ->getOneOrNullResult();
    }

    public function findNewDomainEvents(Domain $domain, \DateTimeImmutable $updatedAt)
    {
        return $this->createQueryBuilder('de')
            ->select()
            ->where('de.domain = :domain')
            ->andWhere('de.date > :updatedAt')
            ->andWhere('de.date < :now')
            ->setParameter('domain', $domain)
            ->setParameter('updatedAt', $updatedAt)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getResult();
    }

    public function setDomainEventAsDeleted(Domain $domain)
    {
        return $this->createQueryBuilder('de')
            ->update()
            ->set('de.deleted', ':deleted')
            ->where('de.domain = :domain')
            ->setParameter('deleted', true)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->execute();
    }

    //    /**
    //     * @return DomainEvent[] Returns an array of DomainEvent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DomainEvent
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
