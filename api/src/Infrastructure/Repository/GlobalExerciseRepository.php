<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\GlobalExercise;
use App\Domain\Enum\MuscleGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GlobalExercise>
 */
class GlobalExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalExercise::class);
    }

    /**
     * @return array<GlobalExercise>
     */
    public function search(?string $query = null, ?MuscleGroup $muscleGroup = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->setParameter('status', 'ACTIVE');

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('LOWER(g.name) LIKE :query OR LOWER(g.equipment) LIKE :query')
               ->setParameter('query', '%' . strtolower(trim($query)) . '%');
        }

        if ($muscleGroup !== null) {
            $qb->andWhere('g.muscleGroup = :muscleGroup')
               ->setParameter('muscleGroup', $muscleGroup);
        }

        return $qb->orderBy('g.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function save(GlobalExercise $exercise, bool $flush = true): void
    {
        $this->getEntityManager()->persist($exercise);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
