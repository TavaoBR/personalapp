<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentWorkoutPlan;
use App\Domain\Entity\StudentWorkoutPlanVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentWorkoutPlanVersion>
 */
class StudentWorkoutPlanVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentWorkoutPlanVersion::class);
    }

    public function findLatestForPlan(StudentWorkoutPlan $plan): ?StudentWorkoutPlanVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.studentWorkoutPlan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(StudentWorkoutPlanVersion $version, bool $flush = true): void
    {
        $this->getEntityManager()->persist($version);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
