<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\MetricDefinition;
use App\Domain\Entity\StudentMetricRecord;
use App\Domain\Entity\StudentProfile;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentMetricRecord>
 */
class StudentMetricRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentMetricRecord::class);
    }

    /**
     * @return array<StudentMetricRecord>
     */
    public function findByStudentAndMetric(
        StudentProfile $student,
        MetricDefinition $metric,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->where('r.student = :student')
            ->andWhere('r.metricDefinition = :metric')
            ->setParameter('student', $student)
            ->setParameter('metric', $metric);

        if ($startDate !== null) {
            $qb->andWhere('r.measuredAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            $qb->andWhere('r.measuredAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('r.measuredAt', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * @return array<StudentMetricRecord>
     */
    public function findAllByStudent(StudentProfile $student): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.student = :student')
            ->setParameter('student', $student)
            ->orderBy('r.measuredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(StudentMetricRecord $record, bool $flush = true): void
    {
        $this->getEntityManager()->persist($record);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
