<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\PhysicalAssessment;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhysicalAssessment>
 */
class PhysicalAssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalAssessment::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?PhysicalAssessment
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<PhysicalAssessment>
     */
    public function findByStudent(StudentProfile $student): array
    {
        return $this->findBy(
            ['student' => $student],
            ['date' => 'DESC', 'createdAt' => 'DESC']
        );
    }

    public function save(PhysicalAssessment $assessment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($assessment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
