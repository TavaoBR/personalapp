<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\WorkoutTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutTemplate>
 */
class WorkoutTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutTemplate::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?WorkoutTemplate
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * @return array<WorkoutTemplate>
     */
    public function findByTenant(Tenant $tenant, ?string $status = 'ACTIVE'): array
    {
        $criteria = ['tenant' => $tenant];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function save(WorkoutTemplate $template, bool $flush = true): void
    {
        $this->getEntityManager()->persist($template);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
