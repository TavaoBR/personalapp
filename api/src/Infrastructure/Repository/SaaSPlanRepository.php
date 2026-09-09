<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\SaaSPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaaSPlan>
 */
class SaaSPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaaSPlan::class);
    }

    public function findByCode(string $code): ?SaaSPlan
    {
        return $this->findOneBy(['code' => strtoupper(trim($code))]);
    }

    /**
     * @return array<SaaSPlan>
     */
    public function findActivePlans(): array
    {
        return $this->findBy(['status' => 'ACTIVE'], ['priceMonthly' => 'ASC']);
    }

    public function save(SaaSPlan $plan, bool $flush = true): void
    {
        $this->getEntityManager()->persist($plan);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
