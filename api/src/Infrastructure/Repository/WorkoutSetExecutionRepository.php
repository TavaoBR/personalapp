<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\WorkoutSetExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutSetExecution>
 */
class WorkoutSetExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSetExecution::class);
    }

    public function save(WorkoutSetExecution $setExecution, bool $flush = true): void
    {
        $this->getEntityManager()->persist($setExecution);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
