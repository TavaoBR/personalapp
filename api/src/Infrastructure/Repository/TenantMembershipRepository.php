<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TenantMembership>
 */
class TenantMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantMembership::class);
    }

    public function findByUserAndTenant(User $user, Tenant $tenant): ?TenantMembership
    {
        return $this->findOneBy([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<TenantMembership>
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * @return array<TenantMembership>
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->findBy(['tenant' => $tenant]);
    }

    public function save(TenantMembership $membership, bool $flush = true): void
    {
        $this->getEntityManager()->persist($membership);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
