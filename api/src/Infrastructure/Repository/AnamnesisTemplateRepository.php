<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\AnamnesisTemplate;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnamnesisTemplate>
 */
class AnamnesisTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnamnesisTemplate::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?AnamnesisTemplate
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<AnamnesisTemplate>
     */
    public function findByTenant(Tenant $tenant, ?string $status = null): array
    {
        $criteria = ['tenant' => $tenant];
        if ($status !== null) {
            $criteria['status'] = $status;
        }

        return $this->findBy($criteria, ['createdAt' => 'DESC']);
    }

    public function save(AnamnesisTemplate $template, bool $flush = true): void
    {
        $this->getEntityManager()->persist($template);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
