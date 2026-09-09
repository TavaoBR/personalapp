<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\RefreshToken;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidByPlainToken(string $plainToken): ?RefreshToken
    {
        $hash = hash('sha256', $plainToken);
        $token = $this->findOneBy(['tokenHash' => $hash, 'revoked' => false]);

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function revokeAllForUser(User $user): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.revoked', ':revoked')
            ->where('r.user = :user')
            ->setParameter('revoked', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function save(RefreshToken $token, bool $flush = true): void
    {
        $this->getEntityManager()->persist($token);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
