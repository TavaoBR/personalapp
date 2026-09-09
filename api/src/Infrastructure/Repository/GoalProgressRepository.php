<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\GoalProgress;
use App\Domain\Entity\StudentGoal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GoalProgress>
 */
class GoalProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GoalProgress::class);
    }

    /**
     * @return array<GoalProgress>
     */
    public function findByGoal(StudentGoal $goal): array
    {
        return $this->findBy(['goal' => $goal], ['recordedAt' => 'DESC', 'createdAt' => 'DESC']);
    }

    public function save(GoalProgress $progress, bool $flush = true): void
    {
        $this->getEntityManager()->persist($progress);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
