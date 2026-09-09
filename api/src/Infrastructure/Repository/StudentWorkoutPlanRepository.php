<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\StudentWorkoutPlan;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentWorkoutPlan>
 */
class StudentWorkoutPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentWorkoutPlan::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentWorkoutPlan
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentWorkoutPlan>
     */
    public function findByStudent(StudentProfile $student, ?string $status = null): array
    {
        $criteria = ['student' => $student];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function findActiveForStudent(StudentProfile $student): ?StudentWorkoutPlan
    {
        return $this->findOneBy([
            'student' => $student,
            'status' => 'ACTIVE',
        ]);
    }

    public function save(StudentWorkoutPlan $plan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plan);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
