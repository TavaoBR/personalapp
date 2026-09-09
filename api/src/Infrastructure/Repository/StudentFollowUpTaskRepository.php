<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentFollowUpPlan;
use App\Domain\Entity\StudentFollowUpTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentFollowUpTask>
 */
class StudentFollowUpTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentFollowUpTask::class);
    }

    public function findById(string $id): ?StudentFollowUpTask
    {
        return $this->find($id);
    }

    /**
     * @return array<StudentFollowUpTask>
     */
    public function findByPlan(StudentFollowUpPlan $plan): array
    {
        return $this->findBy(['plan' => $plan], ['dueDate' => 'ASC', 'orderIndex' => 'ASC']);
    }

    public function save(StudentFollowUpTask $task, bool $flush = true): void
    {
        $this->getEntityManager()->persist($task);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
