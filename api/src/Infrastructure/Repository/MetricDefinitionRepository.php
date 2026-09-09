<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\MetricDefinition;
use App\Domain\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetricDefinition>
 */
class MetricDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetricDefinition::class);
    }

    /**
     * @return array<MetricDefinition>
     */
    public function findAllAvailable(Tenant $tenant): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.tenant = :tenant OR m.tenant IS NULL)')
            ->andWhere('m.status = :status')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'ACTIVE')
            ->orderBy('m.category', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByIdOrCode(string $idOrCode, Tenant $tenant): ?MetricDefinition
    {
        return $this->createQueryBuilder('m')
            ->where('(m.tenant = :tenant OR m.tenant IS NULL)')
            ->andWhere('m.status = :status')
            ->andWhere('(m.id = :val OR m.code = :valUpper)')
            ->setParameter('tenant', $tenant)
            ->setParameter('status', 'ACTIVE')
            ->setParameter('val', $idOrCode)
            ->setParameter('valUpper', strtoupper(trim($idOrCode)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(MetricDefinition $definition, bool $flush = true): void
    {
        $this->getEntityManager()->persist($definition);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
