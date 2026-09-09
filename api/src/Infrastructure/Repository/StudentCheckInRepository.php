<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentCheckIn;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentCheckIn>
 */
class StudentCheckInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentCheckIn::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentCheckIn
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentCheckIn>
     */
    public function findByStudentAndTenant(StudentProfile $student, Tenant $tenant): array
    {
        return $this->findBy([
            'student' => $student,
            'tenant' => $tenant,
        ], ['checkInDate' => 'DESC', 'createdAt' => 'DESC']);
    }

    public function save(StudentCheckIn $checkIn, bool $flush = true): void
    {
        $this->getEntityManager()->persist($checkIn);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
