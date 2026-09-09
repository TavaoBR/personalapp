<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantExercise;
use App\Domain\Enum\MuscleGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TenantExercise>
 */
class TenantExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantExercise::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?TenantExercise
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * @return array<TenantExercise>
     */
    public function search(Tenant $tenant, ?string $query = null, ?MuscleGroup $muscleGroup = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.tenant = :tenant')
            ->andWhere('t.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'ACTIVE');

        if ($query !== null && trim($query) !== '') {
            $qb->andWhere('LOWER(t.name) LIKE :query OR LOWER(t.equipment) LIKE :query')
               ->setParameter('query', '%' . strtolower(trim($query)) . '%');
        }

        if ($muscleGroup !== null) {
            $qb->andWhere('t.muscleGroup = :muscleGroup')
               ->setParameter('muscleGroup', $muscleGroup);
        }

        return $qb->orderBy('t.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function save(TenantExercise $exercise, bool $flush = true): void
    {
        $this->getEntityManager()->persist($exercise);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
