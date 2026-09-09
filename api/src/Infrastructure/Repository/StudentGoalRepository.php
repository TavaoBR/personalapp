<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentGoal;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Enum\GoalStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentGoal>
 */
class StudentGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentGoal::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentGoal
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentGoal>
     */
    public function findByStudentAndTenant(StudentProfile $student, Tenant $tenant, ?GoalStatus $status = null): array
    {
        $criteria = [
            'student' => $student,
            'tenant' => $tenant,
        ];

        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function save(StudentGoal $goal, bool $flush = true): void
    {
        $this->getEntityManager()->persist($goal);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
