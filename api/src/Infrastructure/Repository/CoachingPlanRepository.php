<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\CoachingPlan;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoachingPlan>
 */
class CoachingPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachingPlan::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?CoachingPlan
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<CoachingPlan>
     */
    public function findByTenant(Tenant $tenant, ?string $status = null): array
    {
        $criteria = ['tenant' => $tenant];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['price' => 'ASC', 'name' => 'ASC']);
    }

    public function save(CoachingPlan $plan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plan);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
