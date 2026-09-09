<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\WorkoutTemplate;
use App\Domain\Entity\WorkoutTemplateVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutTemplateVersion>
 */
class WorkoutTemplateVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutTemplateVersion::class);
    }

    public function findLatestForTemplate(WorkoutTemplate $template): ?WorkoutTemplateVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.workoutTemplate = :template')
            ->setParameter('template', $template)
            ->orderBy('v.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<WorkoutTemplateVersion>
     */
    public function findByTemplate(WorkoutTemplate $template): array
    {
        return $this->findBy(
            ['workoutTemplate' => $template],
            ['versionNumber' => 'DESC']
        );
    }

    public function save(WorkoutTemplateVersion $version, bool $flush = true): void
    {
        $this->getEntityManager()->persist($version);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
