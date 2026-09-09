<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentEnrollment;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Enum\EnrollmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentEnrollment>
 */
class StudentEnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentEnrollment::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentEnrollment
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentEnrollment>
     */
    public function findByStudentAndTenant(StudentProfile $student, Tenant $tenant, ?EnrollmentStatus $status = null): array
    {
        $criteria = [
            'student' => $student,
            'tenant' => $tenant,
        ];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['startDate' => 'DESC']);
    }

    public function countActiveByTenant(Tenant $tenant): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.tenant = :tenant')
            ->andWhere('e.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', EnrollmentStatus::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(StudentEnrollment $enrollment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($enrollment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
