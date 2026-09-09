<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\ExerciseVersion;
use App\Domain\Entity\GlobalExercise;
use App\Domain\Entity\TenantExercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExerciseVersion>
 */
class ExerciseVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseVersion::class);
    }

    public function findLatestForTenantExercise(TenantExercise $exercise): ?ExerciseVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.tenantExercise = :exercise')
            ->setParameter('exercise', $exercise)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForGlobalExercise(GlobalExercise $exercise): ?ExerciseVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.globalExercise = :exercise')
            ->setParameter('exercise', $exercise)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(ExerciseVersion $version, bool $flush = true): void
    {
        $this->getEntityManager()->persist($version);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
