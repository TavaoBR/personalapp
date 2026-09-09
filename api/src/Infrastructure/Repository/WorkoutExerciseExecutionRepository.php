<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\WorkoutExerciseExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutExerciseExecution>
 */
class WorkoutExerciseExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutExerciseExecution::class);
    }

    public function save(WorkoutExerciseExecution $execution, bool $flush = true): void
    {
        $this->getEntityManager()->persist($execution);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
