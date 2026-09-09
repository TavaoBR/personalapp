<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\PlatformAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlatformAuditLog>
 */
class PlatformAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatformAuditLog::class);
    }

    public function save(PlatformAuditLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    /**
     * @return list<PlatformAuditLog>
     */
    public function findByFilters(?string $action = null, ?string $targetType = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($action !== null && trim($action) !== '') {
            $qb->andWhere('l.action = :action')
                ->setParameter('action', strtoupper(trim($action)));
        }

        if ($targetType !== null && trim($targetType) !== '') {
            $qb->andWhere('l.targetType = :targetType')
                ->setParameter('targetType', trim($targetType));
        }

        return $qb->getQuery()->getResult();
    }
}
