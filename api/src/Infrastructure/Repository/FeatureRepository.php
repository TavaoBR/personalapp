<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Feature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feature>
 */
class FeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feature::class);
    }

    public function findByCode(string $code): ?Feature
    {
        return $this->findOneBy(['code' => strtoupper(trim($code))]);
    }

    public function save(Feature $feature, bool $flush = true): void
    {
        $this->getEntityManager()->persist($feature);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
