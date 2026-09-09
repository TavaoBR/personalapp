<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function findById(string $id): ?Tenant
    {
        return $this->find($id);
    }

    public function save(Tenant $tenant, bool $flush = true): void
    {
        $this->getEntityManager()->persist($tenant);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
