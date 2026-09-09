<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\StudentTimelineEvent;
use App\Domain\Entity\Tenant;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentTimelineEvent>
 */
class StudentTimelineEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentTimelineEvent::class);
    }

    /**
     * @return array<StudentTimelineEvent>
     */
    public function findTimeline(
        StudentProfile $student,
        Tenant $tenant,
        ?string $eventType = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->where('e.student = :student')
            ->andWhere('e.tenant = :tenant')
            ->setParameter('student', $student)
            ->setParameter('tenant', $tenant);

        if ($eventType !== null && trim($eventType) !== '') {
            $qb->andWhere('e.eventType = :type')
                ->setParameter('type', strtoupper(trim($eventType)));
        }

        if ($startDate !== null) {
            $qb->andWhere('e.occurredAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('e.occurredAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('e.occurredAt', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countTimeline(
        StudentProfile $student,
        Tenant $tenant,
        ?string $eventType = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): int {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.student = :student')
            ->andWhere('e.tenant = :tenant')
            ->setParameter('student', $student)
            ->setParameter('tenant', $tenant);

        if ($eventType !== null && trim($eventType) !== '') {
            $qb->andWhere('e.eventType = :type')
                ->setParameter('type', strtoupper(trim($eventType)));
        }

        if ($startDate !== null) {
            $qb->andWhere('e.occurredAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('e.occurredAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function save(StudentTimelineEvent $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
