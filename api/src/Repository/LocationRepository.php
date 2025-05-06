<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findByIp(string $ipAddress): ?Location
    {
        return $this->createQueryBuilder('l')
            ->where('l.ipAddr = :ipAddress')
            ->setParameter('ipAddress', $ipAddress)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
