<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\AnamnesisTemplate;
use App\Domain\Entity\AnamnesisTemplateVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnamnesisTemplateVersion>
 */
class AnamnesisTemplateVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnamnesisTemplateVersion::class);
    }

    public function findById(string $id): ?AnamnesisTemplateVersion
    {
        return $this->find($id);
    }

    public function findLatestVersion(AnamnesisTemplate $template): ?AnamnesisTemplateVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.anamnesisTemplate = :template')
            ->setParameter('template', $template)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(AnamnesisTemplateVersion $version, bool $flush = true): void
    {
        $this->getEntityManager()->persist($version);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
