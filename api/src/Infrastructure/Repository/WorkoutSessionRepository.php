<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\WorkoutSession;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSession>
 */
class WorkoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSession::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?WorkoutSession
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<WorkoutSession>
     */
    public function findHistoryForStudent(
        StudentProfile $student,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        ?string $status = null
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.student = :student')
            ->setParameter('student', $student);

        if ($startDate !== null) {
            $qb->andWhere('s.startedAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('s.startedAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        if ($status !== null && trim($status) !== '') {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('s.startedAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function save(WorkoutSession $session, bool $flush = true): void
    {
        $this->getEntityManager()->persist($session);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
