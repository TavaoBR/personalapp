<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\StudentWorkoutDay;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\WorkoutExerciseExecution;
use App\Domain\Entity\WorkoutSession;
use App\Domain\Entity\WorkoutSetExecution;
use App\Infrastructure\Repository\StudentWorkoutPlanRepository;
use App\Infrastructure\Repository\WorkoutExerciseExecutionRepository;
use App\Infrastructure\Repository\WorkoutSessionRepository;
use App\Infrastructure\Repository\WorkoutSetExecutionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkoutSessionService
{
    public function __construct(
        private readonly WorkoutSessionRepository $sessionRepository,
        private readonly WorkoutExerciseExecutionRepository $exerciseExecRepository,
        private readonly WorkoutSetExecutionRepository $setExecRepository,
        private readonly StudentWorkoutPlanRepository $planRepository,
        private readonly \App\Infrastructure\Repository\StudentWorkoutPlanVersionRepository $planVersionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function startSession(
        Tenant $tenant,
        StudentProfile $student,
        ?string $workoutDayId = null
    ): WorkoutSession {
        $workoutDay = null;
        $planVersion = null;
        $plan = null;

        if ($workoutDayId !== null && trim($workoutDayId) !== '') {
            $workoutDay = $this->entityManager->getRepository(StudentWorkoutDay::class)->find($workoutDayId);
            if ($workoutDay !== null) {
                $planVersion = $workoutDay->getStudentWorkoutPlanVersion();
                $plan = $planVersion->getStudentWorkoutPlan();
            }
        } else {
            // Se não informou o dia, busca o plano ativo do aluno
            $plan = $this->planRepository->findActiveForStudent($student);
            if ($plan !== null) {
                $planVersion = $this->planVersionRepository->findLatestForPlan($plan);
                $workoutDay = $planVersion?->getDays()->first() ?: null;
            }
        }

        $session = new WorkoutSession(
            tenant: $tenant,
            student: $student,
            workoutPlan: $plan,
            workoutPlanVersion: $planVersion,
            workoutDay: $workoutDay,
            startedAt: new DateTimeImmutable(),
            status: 'IN_PROGRESS'
        );
        $this->sessionRepository->save($session, false);

        // Preenche com os exercícios prescritos do dia
        if ($workoutDay !== null) {
            foreach ($workoutDay->getExercises() as $dayEx) {
                $exec = new WorkoutExerciseExecution(
                    workoutSession: $session,
                    exerciseVersion: $dayEx->getExerciseVersion(),
                    orderIndex: $dayEx->getOrderIndex(),
                    studentWorkoutExercise: $dayEx
                );
                $this->entityManager->persist($exec);
                $session->addExerciseExecution($exec);

                // Cria os sets prescritos
                for ($s = 1; $s <= $dayEx->getSets(); $s++) {
                    $setExec = new WorkoutSetExecution(
                        exerciseExecution: $exec,
                        setNumber: $s,
                        repsPrescribed: $dayEx->getReps(),
                        weightPrescribed: $dayEx->getWeight(),
                        restSeconds: $dayEx->getRestSeconds(),
                        completed: false
                    );
                    $this->entityManager->persist($setExec);
                    $exec->addSetExecution($setExec);
                }
            }
        }

        $this->entityManager->flush();

        return $session;
    }

    /**
     * @return array<string, mixed>
     */
    public function getById(string $id, Tenant $tenant): array
    {
        $session = $this->sessionRepository->findByIdAndTenant($id, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        return $this->formatSessionDetail($session);
    }

    public function recordSetExecution(
        Tenant $tenant,
        string $sessionId,
        string $exerciseExecutionId,
        int $setNumber,
        ?int $repsPerformed = null,
        ?float $weightPerformed = null,
        ?float $rpe = null,
        ?int $restSeconds = null,
        bool $completed = true,
        ?string $notes = null
    ): WorkoutSetExecution {
        $session = $this->sessionRepository->findByIdAndTenant($sessionId, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        $exec = $this->exerciseExecRepository->find($exerciseExecutionId);
        if ($exec === null || $exec->getWorkoutSession()->getId() !== $session->getId()) {
            throw new NotFoundHttpException('Exercício não encontrado nesta sessão.');
        }

        // Procura se o set já existe
        $targetSet = null;
        foreach ($exec->getSetExecutions() as $set) {
            if ($set->getSetNumber() === $setNumber) {
                $targetSet = $set;
                break;
            }
        }

        if ($targetSet === null) {
            $targetSet = new WorkoutSetExecution(
                exerciseExecution: $exec,
                setNumber: $setNumber
            );
            $this->entityManager->persist($targetSet);
            $exec->addSetExecution($targetSet);
        }

        if ($repsPerformed !== null) {
            $targetSet->setRepsPerformed($repsPerformed);
        }
        if ($weightPerformed !== null) {
            $targetSet->setWeightPerformed($weightPerformed);
        }
        if ($rpe !== null) {
            $targetSet->setRpe($rpe);
        }
        if ($restSeconds !== null) {
            $targetSet->setRestSeconds($restSeconds);
        }
        $targetSet->setCompleted($completed);
        if ($notes !== null) {
            $targetSet->setNotes($notes);
        }

        $session->recalculateCompletionPercentage();
        $this->entityManager->flush();

        return $targetSet;
    }

    public function completeSession(
        Tenant $tenant,
        string $sessionId,
        ?string $feedbackNotes = null
    ): WorkoutSession {
        $session = $this->sessionRepository->findByIdAndTenant($sessionId, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        $session->complete(new DateTimeImmutable(), $feedbackNotes);
        $this->sessionRepository->save($session, true);

        return $session;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getHistory(
        Tenant $tenant,
        StudentProfile $student,
        ?string $period = null,
        ?string $status = null
    ): array {
        $startDate = null;
        $endDate = null;

        if ($period !== null) {
            $now = new DateTimeImmutable();
            match (strtolower(trim($period))) {
                'today' => $startDate = $now->setTime(0, 0, 0),
                'week' => $startDate = $now->modify('-7 days'),
                'month', '30d' => $startDate = $now->modify('-30 days'),
                '60d' => $startDate = $now->modify('-60 days'),
                '90d' => $startDate = $now->modify('-90 days'),
                default => null,
            };
        }

        $sessions = $this->sessionRepository->findHistoryForStudent($student, $startDate, $endDate, $status);
        $results = [];

        foreach ($sessions as $session) {
            $results[] = [
                'id' => $session->getId(),
                'workout_day_name' => $session->getWorkoutDay()?->getName(),
                'started_at' => $session->getStartedAt()->format(\DateTimeInterface::ATOM),
                'finished_at' => $session->getFinishedAt()?->format(\DateTimeInterface::ATOM),
                'duration_seconds' => $session->getDurationSeconds(),
                'duration_minutes' => $session->getDurationSeconds() !== null ? round($session->getDurationSeconds() / 60) : null,
                'status' => $session->getStatus(),
                'completion_percentage' => $session->getCompletionPercentage(),
                'feedback_notes' => $session->getFeedbackNotes(),
                'exercises_count' => count($session->getExerciseExecutions()),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSessionDetail(WorkoutSession $session): array
    {
        $exercises = [];
        foreach ($session->getExerciseExecutions() as $exec) {
            $sets = [];
            foreach ($exec->getSetExecutions() as $set) {
                $sets[] = [
                    'id' => $set->getId(),
                    'set_number' => $set->getSetNumber(),
                    'reps_prescribed' => $set->getRepsPrescribed(),
                    'reps_performed' => $set->getRepsPerformed(),
                    'weight_prescribed' => $set->getWeightPrescribed(),
                    'weight_performed' => $set->getWeightPerformed(),
                    'rest_seconds' => $set->getRestSeconds(),
                    'rpe' => $set->getRpe(),
                    'completed' => $set->isCompleted(),
                    'notes' => $set->getNotes(),
                ];
            }

            $ver = $exec->getExerciseVersion();
            $exercises[] = [
                'id' => $exec->getId(),
                'order_index' => $exec->getOrderIndex(),
                'exercise_name' => $ver->getName(),
                'muscle_group' => $ver->getMuscleGroup()->value,
                'equipment' => $ver->getEquipment(),
                'video_url' => $ver->getVideoUrl(),
                'notes' => $exec->getNotes(),
                'sets' => $sets,
            ];
        }

        return [
            'id' => $session->getId(),
            'student_id' => $session->getStudent()->getId(),
            'workout_plan_id' => $session->getWorkoutPlan()?->getId(),
            'workout_plan_name' => $session->getWorkoutPlan()?->getName(),
            'workout_day_id' => $session->getWorkoutDay()?->getId(),
            'workout_day_name' => $session->getWorkoutDay()?->getName(),
            'started_at' => $session->getStartedAt()->format(\DateTimeInterface::ATOM),
            'finished_at' => $session->getFinishedAt()?->format(\DateTimeInterface::ATOM),
            'duration_seconds' => $session->getDurationSeconds(),
            'duration_minutes' => $session->getDurationSeconds() !== null ? round($session->getDurationSeconds() / 60) : null,
            'status' => $session->getStatus(),
            'completion_percentage' => $session->getCompletionPercentage(),
            'feedback_notes' => $session->getFeedbackNotes(),
            'exercises' => $exercises,
        ];
    }
}
