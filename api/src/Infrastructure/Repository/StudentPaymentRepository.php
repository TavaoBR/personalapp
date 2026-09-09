<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StudentPayment;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Enum\PaymentStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentPayment>
 */
class StudentPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentPayment::class);
    }

    public function findByIdAndTenant(string $id, Tenant $tenant): ?StudentPayment
    {
        return $this->findOneBy([
            'id' => $id,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return array<StudentPayment>
     */
    public function findByStudentAndTenant(StudentProfile $student, Tenant $tenant): array
    {
        return $this->findBy(
            ['student' => $student, 'tenant' => $tenant],
            ['dueDate' => 'DESC', 'createdAt' => 'DESC']
        );
    }

    /**
     * @return array<StudentPayment>
     */
    public function findFiltered(
        Tenant $tenant,
        ?PaymentStatus $status = null,
        ?StudentProfile $student = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.tenant = :tenant')
            ->setParameter('tenant', $tenant);

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        if ($student !== null) {
            $qb->andWhere('p.student = :student')
                ->setParameter('student', $student);
        }

        if ($startDate !== null) {
            $qb->andWhere('p.dueDate >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('p.dueDate <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('p.dueDate', 'DESC')->getQuery()->getResult();
    }

    public function save(StudentPayment $payment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($payment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
