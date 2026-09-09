<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentAnamnesis;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentAnamnesis>
 */
class StudentAnamnesisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentAnamnesis::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentAnamnesis
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentAnamnesis>
     */
    public function findByStudent(StudentProfile $student, Tenant $tenant): array
    {
        return $this->findBy(
            [
                'student' => $student,
                'tenant' => $tenant,
            ],
            ['answeredAt' => 'DESC', 'createdAt' => 'DESC']
        );
    }

    public function save(StudentAnamnesis $anamnesis, bool $flush = true): void
    {
        $this->getEntityManager()->persist($anamnesis);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
