<?php

namespace App\Repository;

use App\Config\EventAction;
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

    public function findLastDomainEvent(Domain $domain, string $action)
    {
        return $this->createQueryBuilder('de')
            ->select()
            ->where('de.domain = :domain')
            ->andWhere('de.action = :action')
            ->andWhere('de.deleted = FALSE')
            ->orderBy('de.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setParameter('domain', $domain)
            ->setParameter('action', $action)
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

    public function findLastDomainEventByEventAction(Domain $domain, EventAction $eventAction, bool $deleted): ?DomainEvent
    {
        return $this->createQueryBuilder('de')
            ->select()
            ->where('de.domain = :domain')
            ->andWhere('de.action = :action')
            ->andWhere('de.deleted = :deleted')
            ->orderBy('de.date', 'DESC')
            ->setParameter('deleted', $deleted)
            ->setParameter('domain', $domain)
            ->setParameter('action', $eventAction)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
