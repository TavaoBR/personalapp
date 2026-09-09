<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentFollowUpPlan;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentFollowUpPlan>
 */
class StudentFollowUpPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentFollowUpPlan::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentFollowUpPlan
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentFollowUpPlan>
     */
    public function findByStudentAndTenant(StudentProfile $student, Tenant $tenant, ?string $status = null): array
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

    public function save(StudentFollowUpPlan $plan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plan);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
