<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainerProfile>
 */
class TrainerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainerProfile::class);
    }

    public function findByUserAndTenant(User $user, Tenant $tenant): ?TrainerProfile
    {
        return $this->findOneBy([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<TrainerProfile>
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->findBy(['tenant' => $tenant]);
    }

    public function save(TrainerProfile $trainerProfile, bool $flush = true): void
    {
        $this->getEntityManager()->persist($trainerProfile);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
