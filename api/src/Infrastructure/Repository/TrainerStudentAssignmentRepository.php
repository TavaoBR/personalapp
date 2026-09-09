<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\TrainerStudentAssignment;
use App\Domain\Enum\AssignmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainerStudentAssignment>
 */
class TrainerStudentAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainerStudentAssignment::class);
    }

    /**
     * @return array<TrainerStudentAssignment>
     */
    public function findActiveByTrainer(Tenant $tenant, TrainerProfile $trainer): array
    {
        return $this->findBy([
            'tenant' => $tenant,
            'trainer' => $trainer,
            'status' => AssignmentStatus::ACTIVE,
        ]);
    }

    /**
     * @return array<TrainerStudentAssignment>
     */
    public function findActiveByStudent(Tenant $tenant, StudentProfile $student): array
    {
        return $this->findBy([
            'tenant' => $tenant,
            'student' => $student,
            'status' => AssignmentStatus::ACTIVE,
        ]);
    }

    public function findAssignment(Tenant $tenant, TrainerProfile $trainer, StudentProfile $student): ?TrainerStudentAssignment
    {
        return $this->findOneBy([
            'tenant' => $tenant,
            'trainer' => $trainer,
            'student' => $student,
            'status' => AssignmentStatus::ACTIVE,
        ]);
    }

    public function save(TrainerStudentAssignment $assignment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($assignment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
