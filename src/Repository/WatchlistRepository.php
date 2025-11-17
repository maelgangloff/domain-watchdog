<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Watchlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Watchlist>
 */
class WatchlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Watchlist::class);
    }

    public function getTrackedDomainCount()
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(DISTINCT d.ldhName)')
            ->join('w.domains', 'd')
            ->where('d.deleted = FALSE')
            ->getQuery()->getSingleScalarResult();
    }

    public function getEnabledWatchlist()
    {
        return $this->createQueryBuilder('w')
            ->select()
            ->where('w.enabled = true')
            ->getQuery()->execute();
    }

    /**
     * @return Watchlist[]
     */
    public function fetchWatchlistsForUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->addSelect('d')
            ->addSelect('e')
            ->addSelect('p')
            ->leftJoin('w.domains', 'd')
            ->leftJoin('d.events', 'e')
            ->leftJoin('d.domainPurchases', 'p')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Watchlist[] Returns an array of Watchlist objects
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

    //    public function findOneBySomeField($value): ?Watchlist
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
