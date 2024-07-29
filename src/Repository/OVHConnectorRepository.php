<?php

namespace App\Repository;

use App\Entity\OVHConnector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OVHConnector|null find($id, $lockMode = null, $lockVersion = null)
 * @method OVHConnector|null findOneBy(array $criteria, array $orderBy = null)
 * @method OVHConnector[] findAll()
 * @method OVHConnector[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OVHConnectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OVHConnector::class);
    }
}
