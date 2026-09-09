<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\GoalService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\GoalProgress;
use App\Domain\Entity\StudentGoal;
use App\Domain\Entity\User;
use App\Domain\Enum\GoalStatus;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GoalController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly GoalService $goalService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerRepository
    ) {
    }

    #[Route('/api/students/{id}/goals', name: 'api_student_goals_list', methods: ['GET'])]
    public function listGoals(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_GOALS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $statusFilter = null;
        if ($request->query->has('status')) {
            $statusFilter = GoalStatus::tryFrom((string) $request->query->get('status'));
        }

        $goals = $this->goalService->listGoalsByStudent($tenant, $student, $statusFilter);
        $data = array_map(fn (StudentGoal $g) => $this->serializeGoalSummary($g), $goals);

        return new JsonResponse(['goals' => $data]);
    }

    #[Route('/api/students/{id}/goals', name: 'api_student_goals_create', methods: ['POST'])]
    public function createGoal(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_GOALS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new BadRequestHttpException('O título da meta é obrigatório.');
        }

        $trainer = $this->trainerRepository->findByUserAndTenant($user, $tenant);
        $metricCodeOrId = isset($data['metric_code']) ? (string) $data['metric_code'] : (isset($data['metric_id']) ? (string) $data['metric_id'] : null);
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $initialValue = isset($data['initial_value']) ? (float) $data['initial_value'] : null;
        $targetValue = isset($data['target_value']) ? (float) $data['target_value'] : null;
        $unit = isset($data['unit']) ? (string) $data['unit'] : null;
        $startDate = isset($data['start_date']) ? new DateTimeImmutable((string) $data['start_date']) : null;
        $targetDate = isset($data['target_date']) ? new DateTimeImmutable((string) $data['target_date']) : null;

        $goal = $this->goalService->createGoal(
            tenant: $tenant,
            student: $student,
            title: $title,
            trainer: $trainer,
            metricCodeOrId: $metricCodeOrId,
            description: $description,
            initialValue: $initialValue,
            targetValue: $targetValue,
            unit: $unit,
            startDate: $startDate,
            targetDate: $targetDate
        );

        return new JsonResponse($this->serializeGoalDetail($goal), Response::HTTP_CREATED);
    }

    #[Route('/api/goals/{id}', name: 'api_goals_detail', methods: ['GET'])]
    public function goalDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $goal = $this->goalService->getGoalById($tenant, $id);

        if ($goal === null) {
            throw new NotFoundHttpException('Meta não encontrada.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::VIEW_GOALS);
            $this->authorizationService->assertCanAccessStudent($user, $goal->getStudent());
        } else {
            if ($goal->getStudent()->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Meta não encontrada.');
            }
        }

        return new JsonResponse($this->serializeGoalDetail($goal));
    }

    #[Route('/api/goals/{id}', name: 'api_goals_update', methods: ['PATCH'])]
    public function updateGoal(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_GOALS);

        $goal = $this->goalService->getGoalById($tenant, $id);
        if ($goal === null) {
            throw new NotFoundHttpException('Meta não encontrada.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $goal->getStudent());

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $title = isset($data['title']) ? (string) $data['title'] : null;
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $targetValue = isset($data['target_value']) ? (float) $data['target_value'] : null;
        $unit = isset($data['unit']) ? (string) $data['unit'] : null;
        $targetDate = isset($data['target_date']) ? new DateTimeImmutable((string) $data['target_date']) : null;
        $status = isset($data['status']) ? GoalStatus::tryFrom((string) $data['status']) : null;

        $updatedGoal = $this->goalService->updateGoal(
            tenant: $tenant,
            goalId: $id,
            title: $title,
            description: $description,
            targetValue: $targetValue,
            unit: $unit,
            targetDate: $targetDate,
            status: $status
        );

        return new JsonResponse($this->serializeGoalDetail($updatedGoal));
    }

    #[Route('/api/goals/{id}/progress', name: 'api_goals_record_progress', methods: ['POST'])]
    public function recordProgress(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $goal = $this->goalService->getGoalById($tenant, $id);

        if ($goal === null) {
            throw new NotFoundHttpException('Meta não encontrada.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::MANAGE_GOALS);
            $this->authorizationService->assertCanAccessStudent($user, $goal->getStudent());
            $source = 'TRAINER';
        } else {
            if ($goal->getStudent()->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Meta não encontrada.');
            }
            $source = 'STUDENT';
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];

        if (!isset($data['value'])) {
            throw new BadRequestHttpException('O valor do progresso é obrigatório.');
        }

        $value = (float) $data['value'];
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;
        $recordedAt = isset($data['recorded_at']) ? new DateTimeImmutable((string) $data['recorded_at']) : null;

        $progress = $this->goalService->recordProgress(
            tenant: $tenant,
            goalId: $id,
            value: $value,
            notes: $notes,
            recordedAt: $recordedAt,
            source: $source
        );

        return new JsonResponse([
            'id' => $progress->getId(),
            'value' => $progress->getValue(),
            'previous_value' => $progress->getPreviousValue(),
            'source' => $progress->getSource(),
            'notes' => $progress->getNotes(),
            'recorded_at' => $progress->getRecordedAt()->format(\DateTimeInterface::ATOM),
            'goal_status' => $goal->getStatus()->value,
            'goal_progress_percentage' => $this->goalService->calculateProgressPercentage($goal),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/me/goals', name: 'api_me_goals', methods: ['GET'])]
    public function meGoals(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $goals = $this->goalService->listGoalsByStudent($tenant, $student);
        $data = array_map(fn (StudentGoal $g) => $this->serializeGoalSummary($g), $goals);

        return new JsonResponse(['goals' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGoalSummary(StudentGoal $g): array
    {
        return [
            'id' => $g->getId(),
            'student_id' => $g->getStudent()->getId(),
            'title' => $g->getTitle(),
            'description' => $g->getDescription(),
            'metric' => $g->getMetricDefinition()?->getCode(),
            'initial_value' => $g->getInitialValue(),
            'target_value' => $g->getTargetValue(),
            'current_value' => $g->getCurrentValue(),
            'unit' => $g->getUnit(),
            'progress_percentage' => $this->goalService->calculateProgressPercentage($g),
            'start_date' => $g->getStartDate()->format('Y-m-d'),
            'target_date' => $g->getTargetDate()?->format('Y-m-d'),
            'status' => $g->getStatus()->value,
            'achieved_at' => $g->getAchievedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $g->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGoalDetail(StudentGoal $g): array
    {
        $progress = [];
        foreach ($g->getProgressUpdates() as $p) {
            $progress[] = [
                'id' => $p->getId(),
                'value' => $p->getValue(),
                'previous_value' => $p->getPreviousValue(),
                'source' => $p->getSource(),
                'notes' => $p->getNotes(),
                'recorded_at' => $p->getRecordedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return [
            'id' => $g->getId(),
            'student_id' => $g->getStudent()->getId(),
            'trainer_id' => $g->getTrainer()?->getId(),
            'title' => $g->getTitle(),
            'description' => $g->getDescription(),
            'metric' => $g->getMetricDefinition()?->getCode(),
            'initial_value' => $g->getInitialValue(),
            'target_value' => $g->getTargetValue(),
            'current_value' => $g->getCurrentValue(),
            'unit' => $g->getUnit(),
            'progress_percentage' => $this->goalService->calculateProgressPercentage($g),
            'start_date' => $g->getStartDate()->format('Y-m-d'),
            'target_date' => $g->getTargetDate()?->format('Y-m-d'),
            'status' => $g->getStatus()->value,
            'achieved_at' => $g->getAchievedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $g->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $g->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'progress_history' => $progress,
        ];
    }
}
