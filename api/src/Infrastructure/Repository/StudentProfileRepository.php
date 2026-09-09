<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProfile>
 */
class StudentProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProfile::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentProfile
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    public function findByUserAndTenant(User $user, Tenant $tenant): ?StudentProfile
    {
        return $this->findOneBy([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentProfile>
     */
    public function findByTenant(Tenant $tenant, string $status = 'ACTIVE'): array
    {
        return $this->findBy([
            'tenant' => $tenant,
            'status' => $status,
        ]);
    }

    public function save(StudentProfile $studentProfile, bool $flush = true): void
    {
        $this->getEntityManager()->persist($studentProfile);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
