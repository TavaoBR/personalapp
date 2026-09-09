<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\GoalProgress;
use App\Domain\Entity\MetricDefinition;
use App\Domain\Entity\StudentGoal;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Enum\GoalStatus;
use App\Infrastructure\Repository\GoalProgressRepository;
use App\Infrastructure\Repository\MetricDefinitionRepository;
use App\Infrastructure\Repository\StudentGoalRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GoalService
{
    public function __construct(
        private readonly StudentGoalRepository $goalRepository,
        private readonly GoalProgressRepository $progressRepository,
        private readonly MetricDefinitionRepository $metricDefinitionRepository
    ) {
    }

    public function createGoal(
        Tenant $tenant,
        StudentProfile $student,
        string $title,
        ?TrainerProfile $trainer = null,
        ?string $metricCodeOrId = null,
        ?string $description = null,
        ?float $initialValue = null,
        ?float $targetValue = null,
        ?string $unit = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $targetDate = null
    ): StudentGoal {
        if (trim($title) === '') {
            throw new InvalidArgumentException('O título da meta é obrigatório.');
        }

        $metricDefinition = null;
        if ($metricCodeOrId !== null && trim($metricCodeOrId) !== '') {
            $metricDefinition = $this->metricDefinitionRepository->findByIdOrCode(trim($metricCodeOrId), $tenant);
        }

        $goal = new StudentGoal(
            tenant: $tenant,
            student: $student,
            title: $title,
            trainer: $trainer,
            metricDefinition: $metricDefinition,
            description: $description,
            initialValue: $initialValue,
            targetValue: $targetValue,
            currentValue: $initialValue,
            unit: $unit ?? $metricDefinition?->getUnit(),
            startDate: $startDate,
            targetDate: $targetDate,
            status: GoalStatus::IN_PROGRESS
        );

        if ($initialValue !== null) {
            $initialProgress = new GoalProgress(
                goal: $goal,
                value: $initialValue,
                previousValue: null,
                source: 'MANUAL',
                recordedAt: $startDate ?? new DateTimeImmutable(),
                notes: 'Valor inicial de partida da meta'
            );
            $goal->addProgressUpdate($initialProgress);
        }

        $this->goalRepository->save($goal);

        return $goal;
    }

    public function recordProgress(
        Tenant $tenant,
        string $goalId,
        float $value,
        ?string $notes = null,
        ?DateTimeImmutable $recordedAt = null,
        string $source = 'MANUAL'
    ): GoalProgress {
        $goal = $this->goalRepository->findByIdAndTenant($goalId, $tenant);
        if ($goal === null) {
            throw new NotFoundHttpException('Meta não encontrada.');
        }

        $previousValue = $goal->getCurrentValue();
        $date = $recordedAt ?? new DateTimeImmutable();

        $progress = new GoalProgress(
            goal: $goal,
            value: $value,
            previousValue: $previousValue,
            source: $source,
            recordedAt: $date,
            notes: $notes
        );

        $goal->addProgressUpdate($progress);
        $goal->setCurrentValue($value);

        // Verifica alcance da meta se targetValue estiver definido
        if ($goal->getTargetValue() !== null && $goal->getInitialValue() !== null) {
            $initial = $goal->getInitialValue();
            $target = $goal->getTargetValue();

            $reached = false;
            if ($target > $initial && $value >= $target) {
                $reached = true;
            } elseif ($target < $initial && $value <= $target) {
                $reached = true;
            } elseif ($target === $initial && $value === $target) {
                $reached = true;
            }

            if ($reached && $goal->getStatus() === GoalStatus::IN_PROGRESS) {
                $goal->setStatus(GoalStatus::ACHIEVED);
            }
        }

        $this->progressRepository->save($progress);
        $this->goalRepository->save($goal);

        return $progress;
    }

    public function updateGoal(
        Tenant $tenant,
        string $goalId,
        ?string $title = null,
        ?string $description = null,
        ?float $targetValue = null,
        ?string $unit = null,
        ?DateTimeImmutable $targetDate = null,
        ?GoalStatus $status = null
    ): StudentGoal {
        $goal = $this->goalRepository->findByIdAndTenant($goalId, $tenant);
        if ($goal === null) {
            throw new NotFoundHttpException('Meta não encontrada.');
        }

        if ($title !== null && trim($title) !== '') {
            $goal->setTitle($title);
        }
        if ($description !== null) {
            $goal->setDescription($description);
        }
        if ($targetValue !== null) {
            $goal->setTargetValue($targetValue);
        }
        if ($unit !== null) {
            $goal->setUnit($unit);
        }
        if ($targetDate !== null) {
            $goal->setTargetDate($targetDate);
        }
        if ($status !== null) {
            $goal->setStatus($status);
        }

        $this->goalRepository->save($goal);

        return $goal;
    }

    public function calculateProgressPercentage(StudentGoal $goal): ?float
    {
        $initial = $goal->getInitialValue();
        $target = $goal->getTargetValue();
        $current = $goal->getCurrentValue();

        if ($initial === null || $target === null || $current === null) {
            return null;
        }

        if ($target === $initial) {
            return $current === $target ? 100.0 : 0.0;
        }

        if ($target > $initial) {
            $pct = (($current - $initial) / ($target - $initial)) * 100.0;
        } else {
            $pct = (($initial - $current) / ($initial - $target)) * 100.0;
        }

        return round(max(0.0, $pct), 1);
    }

    /**
     * @return array<StudentGoal>
     */
    public function listGoalsByStudent(Tenant $tenant, StudentProfile $student, ?GoalStatus $status = null): array
    {
        return $this->goalRepository->findByStudentAndTenant($student, $tenant, $status);
    }

    public function getGoalById(Tenant $tenant, string $goalId): ?StudentGoal
    {
        return $this->goalRepository->findByIdAndTenant($goalId, $tenant);
    }
}
