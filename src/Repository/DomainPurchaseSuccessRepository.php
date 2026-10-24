<?php

namespace App\Repository;

use App\Entity\DomainPurchase;
use App\Entity\DomainPurchaseFailure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainPurchase>
 */
class DomainPurchaseSuccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainPurchaseFailure::class);
    }
}
