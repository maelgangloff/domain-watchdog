<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\Tld;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 */
class DomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domain::class);
    }

    public function findByTld(string $tld): array
    {
        return $this->createQueryBuilder('d')
                ->addSelect('events')
                ->leftJoin('d.events', 'events')
                ->where('d.tld = :dot')
                ->setParameter('dot', $tld)
                ->getQuery()
                ->getResult();
    }

    public function getActiveDomainCountByTld(): array
    {
        return $this->createQueryBuilder('d')
            ->select('t.tld tld')
            ->join('d.tld', 't')
            ->addSelect('COUNT(d.ldhName) AS domain')
            ->addGroupBy('t.tld')
            ->where('d.deleted = FALSE')
            ->orderBy('domain', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getArrayResult();
    }

    public function setDomainDeletedIfTldIsDeleted()
    {
        return $this->createQueryBuilder('d')
            ->update()
            ->set('d.deleted', ':deleted')
            ->where('d.tld IN (SELECT t FROM '.Tld::class.' t WHERE t.deletedAt IS NOT NULL)')
            ->setParameter('deleted', true)
            ->getQuery()->execute();
    }

    public function getMyTrackedDomains(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.watchlists', 'w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
