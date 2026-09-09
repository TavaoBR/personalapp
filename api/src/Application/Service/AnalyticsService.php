<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\WorkoutSession;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\StudentWorkoutPlanRepository;
use App\Infrastructure\Repository\WorkoutSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    public function __construct(
        private readonly WorkoutSessionRepository $sessionRepository,
        private readonly StudentProfileRepository $studentRepository,
        private readonly StudentWorkoutPlanRepository $planRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStudentAnalytics(
        Tenant $tenant,
        StudentProfile $student,
        ?DateTimeImmutable $startA = null,
        ?DateTimeImmutable $endA = null,
        ?DateTimeImmutable $startB = null,
        ?DateTimeImmutable $endB = null
    ): array {
        $now = new DateTimeImmutable();
        $startPeriodA = $startA ?? $now->modify('-30 days');
        $endPeriodA = $endA ?? $now;

        $metricsA = $this->calculatePeriodMetrics($student, $startPeriodA, $endPeriodA);

        $comparison = null;
        if ($startB !== null && $endB !== null) {
            $metricsB = $this->calculatePeriodMetrics($student, $startB, $endB);
            $comparison = [
                'period_b' => $metricsB,
                'diff_sessions' => $metricsA['total_sessions'] - $metricsB['total_sessions'],
                'diff_volume' => round($metricsA['total_volume_kg'] - $metricsB['total_volume_kg'], 1),
                'diff_completion_rate' => round($metricsA['completion_rate'] - $metricsB['completion_rate'], 1),
            ];
        }

        return [
            'period_a' => $metricsA,
            'comparison' => $comparison,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatePeriodMetrics(
        StudentProfile $student,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $sessions = $this->sessionRepository->findHistoryForStudent($student, $startDate, $endDate);

        $totalSessions = count($sessions);
        $completedSessions = 0;
        $totalDurationSeconds = 0;
        $totalCompletionPct = 0.0;
        $totalVolumeKg = 0.0;

        foreach ($sessions as $s) {
            if ($s->getStatus() === 'COMPLETED') {
                $completedSessions++;
            }
            if ($s->getDurationSeconds() !== null) {
                $totalDurationSeconds += $s->getDurationSeconds();
            }
            $totalCompletionPct += $s->getCompletionPercentage();

            foreach ($s->getExerciseExecutions() as $ex) {
                foreach ($ex->getSetExecutions() as $set) {
                    if ($set->isCompleted() && $set->getWeightPerformed() !== null && $set->getRepsPerformed() !== null) {
                        $totalVolumeKg += ($set->getWeightPerformed() * $set->getRepsPerformed());
                    }
                }
            }
        }

        $avgDurationMinutes = $totalSessions > 0 ? round(($totalDurationSeconds / $totalSessions) / 60, 1) : 0.0;
        $avgCompletionPct = $totalSessions > 0 ? round($totalCompletionPct / $totalSessions, 1) : 0.0;

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'completion_rate' => $avgCompletionPct,
            'avg_duration_minutes' => $avgDurationMinutes,
            'total_volume_kg' => round($totalVolumeKg, 1),
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getPersonalRecords(Tenant $tenant, StudentProfile $student): array
    {
        $sessions = $this->sessionRepository->findHistoryForStudent($student);
        $prs = [];

        foreach ($sessions as $s) {
            foreach ($s->getExerciseExecutions() as $ex) {
                $exerciseName = $ex->getExerciseVersion()->getName();
                $muscleGroup = $ex->getExerciseVersion()->getMuscleGroup()->value;

                foreach ($ex->getSetExecutions() as $set) {
                    $weight = $set->getWeightPerformed();
                    $reps = $set->getRepsPerformed();

                    if ($weight !== null && $weight > 0) {
                        if (!isset($prs[$exerciseName]) || $weight > $prs[$exerciseName]['max_weight']) {
                            $prs[$exerciseName] = [
                                'exercise_name' => $exerciseName,
                                'muscle_group' => $muscleGroup,
                                'max_weight' => $weight,
                                'reps_at_max' => $reps,
                                'date' => $s->getStartedAt()->format('Y-m-d'),
                            ];
                        }
                    }
                }
            }
        }

        ksort($prs);
        return array_values($prs);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPortfolioDashboard(Tenant $tenant, ?TrainerProfile $trainer = null): array
    {
        $allStudents = $trainer !== null
            ? $this->studentRepository->findAssignedToTrainer($tenant, $trainer)
            : $this->studentRepository->findByTenant($tenant);

        $now = new DateTimeImmutable();
        $sevenDaysAgo = $now->modify('-7 days');
        $fourteenDaysAgo = $now->modify('-14 days');
        $thirtyDaysAgo = $now->modify('-30 days');

        $activeStudentsCount = 0;
        $withoutActivePlanCount = 0;
        $atRiskCount = 0;

        foreach ($allStudents as $st) {
            if ($st->getStatus() === 'ACTIVE') {
                $activeStudentsCount++;
            }

            $activePlan = $this->planRepository->findActiveForStudent($st);
            if ($activePlan === null) {
                $withoutActivePlanCount++;
            }

            // Checa última sessão de treino
            $recentSessions = $this->sessionRepository->findHistoryForStudent($st, $fourteenDaysAgo);
            if (empty($recentSessions)) {
                $atRiskCount++;
            }
        }

        // Treinos totais no tenant nos últimos 7 e 30 dias
        $qb7 = $this->entityManager->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(WorkoutSession::class, 's')
            ->where('s.tenant = :tenant')
            ->andWhere('s.startedAt >= :since')
            ->setParameter('tenant', $tenant)
            ->setParameter('since', $sevenDaysAgo);
        $workoutsLast7Days = (int) $qb7->getQuery()->getSingleScalarResult();

        $qb30 = $this->entityManager->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(WorkoutSession::class, 's')
            ->where('s.tenant = :tenant')
            ->andWhere('s.startedAt >= :since')
            ->setParameter('tenant', $tenant)
            ->setParameter('since', $thirtyDaysAgo);
        $workoutsLast30Days = (int) $qb30->getQuery()->getSingleScalarResult();

        return [
            'total_students' => count($allStudents),
            'active_students' => $activeStudentsCount,
            'students_without_active_plan' => $withoutActivePlanCount,
            'students_at_risk' => $atRiskCount,
            'workouts_last_7_days' => $workoutsLast7Days,
            'workouts_last_30_days' => $workoutsLast30Days,
        ];
    }
}
