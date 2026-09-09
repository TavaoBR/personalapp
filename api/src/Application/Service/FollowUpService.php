<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\FollowUpPlanStep;
use App\Domain\Entity\FollowUpPlanTemplate;
use App\Domain\Entity\FollowUpPlanVersion;
use App\Domain\Entity\StudentCheckIn;
use App\Domain\Entity\StudentFollowUpPlan;
use App\Domain\Entity\StudentFollowUpTask;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Infrastructure\Repository\FollowUpPlanTemplateRepository;
use App\Infrastructure\Repository\FollowUpPlanVersionRepository;
use App\Infrastructure\Repository\StudentCheckInRepository;
use App\Infrastructure\Repository\StudentFollowUpPlanRepository;
use App\Infrastructure\Repository\StudentFollowUpTaskRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FollowUpService
{
    private const VALID_STEP_TYPES = [
        'ANAMNESIS',
        'ASSESSMENT',
        'WORKOUT_REVIEW',
        'CHECK_IN',
        'GOAL_REVIEW',
        'CUSTOM',
    ];

    public function __construct(
        private readonly FollowUpPlanTemplateRepository $templateRepository,
        private readonly FollowUpPlanVersionRepository $versionRepository,
        private readonly StudentFollowUpPlanRepository $planRepository,
        private readonly StudentFollowUpTaskRepository $taskRepository,
        private readonly StudentCheckInRepository $checkInRepository
    ) {
    }

    /**
     * @param array<array<string, mixed>> $stepsData
     */
    public function createTemplate(
        Tenant $tenant,
        string $title,
        int $durationDays = 30,
        ?string $description = null,
        array $stepsData = []
    ): FollowUpPlanTemplate {
        if (trim($title) === '') {
            throw new InvalidArgumentException('O título do template é obrigatório.');
        }

        $template = new FollowUpPlanTemplate($tenant, $title, $durationDays, $description);
        $version = new FollowUpPlanVersion($template, 1, 'PUBLISHED');
        $template->addVersion($version);

        $order = 0;
        foreach ($stepsData as $sData) {
            $step = $this->buildStep($version, $sData, $order++);
            $version->addStep($step);
        }

        $this->templateRepository->save($template);

        return $template;
    }

    /**
     * @param array<array<string, mixed>> $stepsData
     */
    public function createVersion(
        Tenant $tenant,
        string $templateId,
        array $stepsData = []
    ): FollowUpPlanVersion {
        $template = $this->templateRepository->findByIdAndTenant($templateId, $tenant);
        if ($template === null) {
            throw new NotFoundHttpException('Template de acompanhamento não encontrado.');
        }

        $latestVersion = $this->versionRepository->findLatestVersion($template);
        $nextVersionNumber = $latestVersion ? $latestVersion->getVersionNumber() + 1 : 1;

        $version = new FollowUpPlanVersion($template, $nextVersionNumber, 'PUBLISHED');
        $template->addVersion($version);

        $order = 0;
        foreach ($stepsData as $sData) {
            $step = $this->buildStep($version, $sData, $order++);
            $version->addStep($step);
        }

        $template->touch();
        $this->versionRepository->save($version);

        return $version;
    }

    public function getTemplate(Tenant $tenant, string $templateId): ?FollowUpPlanTemplate
    {
        return $this->templateRepository->findByIdAndTenant($templateId, $tenant);
    }

    /**
     * @return array<FollowUpPlanTemplate>
     */
    public function listTemplates(Tenant $tenant, ?string $status = null): array
    {
        return $this->templateRepository->findByTenant($tenant, $status);
    }

    /**
     * Prescreve plano ao aluno com isolamento absoluto (cópia profunda dos steps).
     *
     * @param array<array<string, mixed>> $customTasks
     */
    public function prescribePlan(
        Tenant $tenant,
        StudentProfile $student,
        ?TrainerProfile $trainer = null,
        ?string $versionId = null,
        ?string $customTitle = null,
        ?DateTimeImmutable $startDate = null,
        array $customTasks = []
    ): StudentFollowUpPlan {
        $start = $startDate ?? new DateTimeImmutable();
        $version = null;
        $title = $customTitle;
        $endDate = null;

        if ($versionId !== null && trim($versionId) !== '') {
            $version = $this->versionRepository->findById($versionId);
            if ($version === null || $version->getTemplate()->getTenant()->getId() !== $tenant->getId()) {
                throw new NotFoundHttpException('Versão do template de acompanhamento não encontrada.');
            }

            if ($title === null || trim($title) === '') {
                $title = $version->getTemplate()->getTitle();
            }

            $duration = $version->getTemplate()->getDurationDays();
            $endDate = $start->modify("+{$duration} days");
        }

        if ($title === null || trim($title) === '') {
            $title = 'Plano de Acompanhamento Individual';
        }

        $plan = new StudentFollowUpPlan(
            tenant: $tenant,
            student: $student,
            title: $title,
            trainer: $trainer,
            templateVersion: $version,
            startDate: $start,
            endDate: $endDate
        );

        if ($version !== null) {
            // Cópia profunda e cálculo das datas com base no dayOffset
            foreach ($version->getSteps() as $step) {
                $offset = max(1, $step->getDayOffset()) - 1;
                $taskDueDate = $start->modify("+{$offset} days");

                $task = new StudentFollowUpTask(
                    plan: $plan,
                    title: $step->getTitle(),
                    taskType: $step->getStepType(),
                    dueDate: $taskDueDate,
                    orderIndex: $step->getOrderIndex(),
                    notes: $step->getDescription()
                );

                $plan->addTask($task);
            }
        }

        foreach ($customTasks as $idx => $cTask) {
            $taskTitle = trim((string) ($cTask['title'] ?? ''));
            if ($taskTitle === '') {
                continue;
            }

            $taskType = strtoupper(trim((string) ($cTask['type'] ?? $cTask['taskType'] ?? 'CUSTOM')));
            $dueDate = isset($cTask['due_date']) ? new DateTimeImmutable((string) $cTask['due_date']) : $start;
            $orderIndex = isset($cTask['order_index']) ? (int) $cTask['order_index'] : $idx;
            $notes = isset($cTask['notes']) ? (string) $cTask['notes'] : null;

            $task = new StudentFollowUpTask(
                plan: $plan,
                title: $taskTitle,
                taskType: $taskType,
                dueDate: $dueDate,
                orderIndex: $orderIndex,
                notes: $notes
            );

            $plan->addTask($task);
        }

        $this->planRepository->save($plan);

        return $plan;
    }

    public function updateTaskStatus(
        Tenant $tenant,
        string $taskId,
        string $status,
        ?string $notes = null
    ): StudentFollowUpTask {
        $task = $this->taskRepository->findById($taskId);
        if ($task === null || $task->getPlan()->getTenant()->getId() !== $tenant->getId()) {
            throw new NotFoundHttpException('Tarefa de acompanhamento não encontrada.');
        }

        $status = strtoupper(trim($status));
        $task->setStatus($status);
        if ($notes !== null) {
            $task->setNotes($notes);
        }

        $this->taskRepository->save($task);

        return $task;
    }

    public function submitCheckIn(
        Tenant $tenant,
        StudentProfile $student,
        ?string $taskId = null,
        ?DateTimeImmutable $checkInDate = null,
        ?int $energyLevel = null,
        ?int $sleepQuality = null,
        ?int $sorenessLevel = null,
        ?int $dietAdherence = null,
        ?string $notes = null
    ): StudentCheckIn {
        $task = null;
        if ($taskId !== null && trim($taskId) !== '') {
            $task = $this->taskRepository->findById($taskId);
            if ($task !== null && $task->getPlan()->getTenant()->getId() === $tenant->getId()) {
                $task->setStatus('COMPLETED');
                $this->taskRepository->save($task);
            }
        }

        $checkIn = new StudentCheckIn(
            tenant: $tenant,
            student: $student,
            task: $task,
            checkInDate: $checkInDate ?? new DateTimeImmutable(),
            energyLevel: $energyLevel,
            sleepQuality: $sleepQuality,
            sorenessLevel: $sorenessLevel,
            dietAdherence: $dietAdherence,
            notes: $notes
        );

        $this->checkInRepository->save($checkIn);

        return $checkIn;
    }

    public function reviewCheckIn(
        Tenant $tenant,
        string $checkInId,
        string $feedback
    ): StudentCheckIn {
        $checkIn = $this->checkInRepository->findByIdAndTenant($checkInId, $tenant);
        if ($checkIn === null) {
            throw new NotFoundHttpException('Check-in não encontrado.');
        }

        $checkIn->setTrainerFeedback($feedback);
        $this->checkInRepository->save($checkIn);

        return $checkIn;
    }

    /**
     * @return array<StudentFollowUpPlan>
     */
    public function listPlansByStudent(Tenant $tenant, StudentProfile $student, ?string $status = null): array
    {
        return $this->planRepository->findByStudentAndTenant($student, $tenant, $status);
    }

    public function getPlanById(Tenant $tenant, string $planId): ?StudentFollowUpPlan
    {
        return $this->planRepository->findByIdAndTenant($planId, $tenant);
    }

    /**
     * @return array<StudentCheckIn>
     */
    public function listCheckInsByStudent(Tenant $tenant, StudentProfile $student): array
    {
        return $this->checkInRepository->findByStudentAndTenant($student, $tenant);
    }

    public function getCheckInById(Tenant $tenant, string $id): ?StudentCheckIn
    {
        return $this->checkInRepository->findByIdAndTenant($id, $tenant);
    }

    public function getTaskById(Tenant $tenant, string $taskId): ?StudentFollowUpTask
    {
        $task = $this->taskRepository->findById($taskId);
        if ($task === null || $task->getPlan()->getTenant()->getId() !== $tenant->getId()) {
            return null;
        }

        return $task;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildStep(FollowUpPlanVersion $version, array $data, int $defaultOrder): FollowUpPlanStep
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('O título da etapa é obrigatório.');
        }

        $type = strtoupper(trim((string) ($data['stepType'] ?? $data['type'] ?? 'CUSTOM')));
        if (!in_array($type, self::VALID_STEP_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de etapa inválido: {$type}");
        }

        $dayOffset = isset($data['dayOffset']) ? (int) $data['dayOffset'] : (isset($data['day_offset']) ? (int) $data['day_offset'] : 1);
        $orderIndex = isset($data['orderIndex']) ? (int) $data['orderIndex'] : $defaultOrder;
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $configuration = isset($data['configuration']) && is_array($data['configuration']) ? $data['configuration'] : null;

        return new FollowUpPlanStep(
            version: $version,
            title: $title,
            stepType: $type,
            dayOffset: max(1, $dayOffset),
            orderIndex: $orderIndex,
            description: $description,
            configuration: $configuration
        );
    }
}
