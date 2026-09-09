<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\FollowUpPlanTemplate;
use App\Domain\Entity\FollowUpPlanVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FollowUpPlanVersion>
 */
class FollowUpPlanVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FollowUpPlanVersion::class);
    }

    public function findById(string $id): ?FollowUpPlanVersion
    {
        return $this->find($id);
    }

    public function findLatestVersion(FollowUpPlanTemplate $template): ?FollowUpPlanVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.template = :template')
            ->setParameter('template', $template)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(FollowUpPlanVersion $version, bool $flush = true): void
    {
        $this->getEntityManager()->persist($version);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
