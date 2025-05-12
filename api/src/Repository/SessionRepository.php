<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function findActiveSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.active = true')
            ->getQuery()
            ->getResult();
    }

    public function findActiveExpiredSessions(?int $expirationDelay): array
    {
        $fiveMinutesAgo = new \DateTime();
        if ($expirationDelay !== null) {
            $fiveMinutesAgo->modify("-" . $expirationDelay . " minutes");
        }

        return $this->createQueryBuilder('s')
            ->where('s.active = true')
            ->andWhere('s.lastUpdated < :fiveMinutesAgo')
            ->setParameter('fiveMinutesAgo', $fiveMinutesAgo)
            ->getQuery()
            ->getResult();
    }
}
