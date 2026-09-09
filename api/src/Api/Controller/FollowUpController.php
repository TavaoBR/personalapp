<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\FollowUpService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\FollowUpPlanStep;
use App\Domain\Entity\FollowUpPlanTemplate;
use App\Domain\Entity\FollowUpPlanVersion;
use App\Domain\Entity\StudentCheckIn;
use App\Domain\Entity\StudentFollowUpPlan;
use App\Domain\Entity\StudentFollowUpTask;
use App\Domain\Entity\User;
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

final class FollowUpController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly FollowUpService $followUpService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerRepository
    ) {
    }

    #[Route('/api/follow-up-templates', name: 'api_follow_up_templates_list', methods: ['GET'])]
    public function listTemplates(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FOLLOW_UP);

        $templates = $this->followUpService->listTemplates($tenant);
        $data = array_map(function (FollowUpPlanTemplate $t) {
            $latest = $t->getVersions()->first();
            return [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'description' => $t->getDescription(),
                'duration_days' => $t->getDurationDays(),
                'status' => $t->getStatus(),
                'latest_version' => $latest ? $latest->getVersionNumber() : 1,
                'created_at' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $templates);

        return new JsonResponse(['templates' => $data]);
    }

    #[Route('/api/follow-up-templates', name: 'api_follow_up_templates_create', methods: ['POST'])]
    public function createTemplate(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new BadRequestHttpException('O título do template é obrigatório.');
        }

        $durationDays = isset($data['duration_days']) ? (int) $data['duration_days'] : 30;
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $steps = (array) ($data['steps'] ?? []);

        $template = $this->followUpService->createTemplate(
            tenant: $tenant,
            title: $title,
            durationDays: $durationDays,
            description: $description,
            stepsData: $steps
        );

        return new JsonResponse($this->serializeTemplate($template), Response::HTTP_CREATED);
    }

    #[Route('/api/follow-up-templates/{id}', name: 'api_follow_up_templates_detail', methods: ['GET'])]
    public function templateDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FOLLOW_UP);

        $template = $this->followUpService->getTemplate($tenant, $id);
        if ($template === null) {
            throw new NotFoundHttpException('Template de acompanhamento não encontrado.');
        }

        return new JsonResponse($this->serializeTemplate($template));
    }

    #[Route('/api/follow-up-templates/{id}/versions', name: 'api_follow_up_templates_create_version', methods: ['POST'])]
    public function createVersion(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $steps = (array) ($data['steps'] ?? []);

        $version = $this->followUpService->createVersion(
            tenant: $tenant,
            templateId: $id,
            stepsData: $steps
        );

        return new JsonResponse($this->serializeVersion($version), Response::HTTP_CREATED);
    }

    #[Route('/api/students/{id}/follow-up-plans', name: 'api_student_follow_up_plans_list', methods: ['GET'])]
    public function listStudentPlans(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FOLLOW_UP);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $plans = $this->followUpService->listPlansByStudent($tenant, $student);
        $data = array_map(fn (StudentFollowUpPlan $p) => $this->serializePlanSummary($p), $plans);

        return new JsonResponse(['plans' => $data]);
    }

    #[Route('/api/students/{id}/follow-up-plans', name: 'api_student_follow_up_plans_prescribe', methods: ['POST'])]
    public function prescribePlan(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $trainer = $this->trainerRepository->findByUserAndTenant($user, $tenant);
        $versionId = isset($data['version_id']) ? (string) $data['version_id'] : (isset($data['template_version_id']) ? (string) $data['template_version_id'] : null);
        $customTitle = isset($data['title']) ? (string) $data['title'] : null;
        $startDate = isset($data['start_date']) ? new DateTimeImmutable((string) $data['start_date']) : null;
        $customTasks = (array) ($data['tasks'] ?? []);

        $plan = $this->followUpService->prescribePlan(
            tenant: $tenant,
            student: $student,
            trainer: $trainer,
            versionId: $versionId,
            customTitle: $customTitle,
            startDate: $startDate,
            customTasks: $customTasks
        );

        return new JsonResponse($this->serializePlanDetail($plan), Response::HTTP_CREATED);
    }

    #[Route('/api/follow-up-plans/{id}', name: 'api_follow_up_plans_detail', methods: ['GET'])]
    public function planDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $plan = $this->followUpService->getPlanById($tenant, $id);

        if ($plan === null) {
            throw new NotFoundHttpException('Plano de acompanhamento não encontrado.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::VIEW_FOLLOW_UP);
            $this->authorizationService->assertCanAccessStudent($user, $plan->getStudent());
        } else {
            if ($plan->getStudent()->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Plano de acompanhamento não encontrado.');
            }
        }

        return new JsonResponse($this->serializePlanDetail($plan));
    }

    #[Route('/api/follow-up-tasks/{id}', name: 'api_follow_up_tasks_update', methods: ['PATCH'])]
    public function updateTask(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $task = $this->followUpService->getTaskById($tenant, $id);

        if ($task === null) {
            throw new NotFoundHttpException('Tarefa de acompanhamento não encontrada.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);
            $this->authorizationService->assertCanAccessStudent($user, $task->getPlan()->getStudent());
        } else {
            if ($task->getPlan()->getStudent()->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Tarefa de acompanhamento não encontrada.');
            }
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $status = (string) ($data['status'] ?? 'COMPLETED');
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $updatedTask = $this->followUpService->updateTaskStatus($tenant, $id, $status, $notes);

        return new JsonResponse([
            'id' => $updatedTask->getId(),
            'title' => $updatedTask->getTitle(),
            'status' => $updatedTask->getStatus(),
            'completed_at' => $updatedTask->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            'notes' => $updatedTask->getNotes(),
        ]);
    }

    #[Route('/api/students/{id}/check-ins', name: 'api_student_check_ins_create', methods: ['POST'])]
    public function createCheckIn(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);

        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $role = $this->tenantContext->getRole();
        if ($role !== TenantRole::STUDENT) {
            $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);
            $this->authorizationService->assertCanAccessStudent($user, $student);
        } else {
            if ($student->getUser()->getId() !== $user->getId()) {
                throw new NotFoundHttpException('Aluno não encontrado.');
            }
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $taskId = isset($data['task_id']) ? (string) $data['task_id'] : null;
        $checkInDate = isset($data['check_in_date']) ? new DateTimeImmutable((string) $data['check_in_date']) : null;
        $energyLevel = isset($data['energy_level']) ? (int) $data['energy_level'] : null;
        $sleepQuality = isset($data['sleep_quality']) ? (int) $data['sleep_quality'] : null;
        $sorenessLevel = isset($data['soreness_level']) ? (int) $data['soreness_level'] : null;
        $dietAdherence = isset($data['diet_adherence']) ? (int) $data['diet_adherence'] : null;
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $checkIn = $this->followUpService->submitCheckIn(
            tenant: $tenant,
            student: $student,
            taskId: $taskId,
            checkInDate: $checkInDate,
            energyLevel: $energyLevel,
            sleepQuality: $sleepQuality,
            sorenessLevel: $sorenessLevel,
            dietAdherence: $dietAdherence,
            notes: $notes
        );

        return new JsonResponse($this->serializeCheckIn($checkIn), Response::HTTP_CREATED);
    }

    #[Route('/api/students/{id}/check-ins', name: 'api_student_check_ins_list', methods: ['GET'])]
    public function listStudentCheckIns(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_FOLLOW_UP);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $checkIns = $this->followUpService->listCheckInsByStudent($tenant, $student);
        $data = array_map(fn (StudentCheckIn $c) => $this->serializeCheckIn($c), $checkIns);

        return new JsonResponse(['check_ins' => $data]);
    }

    #[Route('/api/check-ins/{id}/feedback', name: 'api_check_ins_feedback', methods: ['POST'])]
    public function feedbackCheckIn(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_FOLLOW_UP);

        $checkIn = $this->followUpService->getCheckInById($tenant, $id);
        if ($checkIn === null) {
            throw new NotFoundHttpException('Check-in não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $checkIn->getStudent());

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $feedback = trim((string) ($data['feedback'] ?? ''));

        if ($feedback === '') {
            throw new BadRequestHttpException('O feedback do treinador é obrigatório.');
        }

        $updated = $this->followUpService->reviewCheckIn($tenant, $id, $feedback);

        return new JsonResponse($this->serializeCheckIn($updated));
    }

    #[Route('/api/me/follow-up-plans', name: 'api_me_follow_up_plans', methods: ['GET'])]
    public function mePlans(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $plans = $this->followUpService->listPlansByStudent($tenant, $student);
        $data = array_map(fn (StudentFollowUpPlan $p) => $this->serializePlanSummary($p), $plans);

        return new JsonResponse(['plans' => $data]);
    }

    #[Route('/api/me/check-ins', name: 'api_me_check_ins', methods: ['GET'])]
    public function meCheckIns(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $checkIns = $this->followUpService->listCheckInsByStudent($tenant, $student);
        $data = array_map(fn (StudentCheckIn $c) => $this->serializeCheckIn($c), $checkIns);

        return new JsonResponse(['check_ins' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTemplate(FollowUpPlanTemplate $t): array
    {
        $versions = [];
        foreach ($t->getVersions() as $v) {
            $versions[] = $this->serializeVersion($v);
        }

        return [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'description' => $t->getDescription(),
            'duration_days' => $t->getDurationDays(),
            'status' => $t->getStatus(),
            'created_at' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $t->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'versions' => $versions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVersion(FollowUpPlanVersion $v): array
    {
        $steps = [];
        foreach ($v->getSteps() as $s) {
            $steps[] = [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'step_type' => $s->getStepType(),
                'day_offset' => $s->getDayOffset(),
                'order_index' => $s->getOrderIndex(),
                'description' => $s->getDescription(),
                'configuration' => $s->getConfiguration(),
            ];
        }

        return [
            'id' => $v->getId(),
            'version_number' => $v->getVersionNumber(),
            'status' => $v->getStatus(),
            'published_at' => $v->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $v->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'steps' => $steps,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlanSummary(StudentFollowUpPlan $p): array
    {
        return [
            'id' => $p->getId(),
            'student_id' => $p->getStudent()->getId(),
            'title' => $p->getTitle(),
            'start_date' => $p->getStartDate()->format('Y-m-d'),
            'end_date' => $p->getEndDate()?->format('Y-m-d'),
            'status' => $p->getStatus(),
            'tasks_count' => $p->getTasks()->count(),
            'created_at' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlanDetail(StudentFollowUpPlan $p): array
    {
        $tasks = [];
        foreach ($p->getTasks() as $t) {
            $tasks[] = [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'task_type' => $t->getTaskType(),
                'due_date' => $t->getDueDate()->format('Y-m-d'),
                'status' => $t->getStatus(),
                'completed_at' => $t->getCompletedAt()?->format(\DateTimeInterface::ATOM),
                'order_index' => $t->getOrderIndex(),
                'notes' => $t->getNotes(),
            ];
        }

        return [
            'id' => $p->getId(),
            'student_id' => $p->getStudent()->getId(),
            'trainer_id' => $p->getTrainer()?->getId(),
            'title' => $p->getTitle(),
            'start_date' => $p->getStartDate()->format('Y-m-d'),
            'end_date' => $p->getEndDate()?->format('Y-m-d'),
            'status' => $p->getStatus(),
            'created_at' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'tasks' => $tasks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCheckIn(StudentCheckIn $c): array
    {
        return [
            'id' => $c->getId(),
            'student_id' => $c->getStudent()->getId(),
            'task_id' => $c->getTask()?->getId(),
            'check_in_date' => $c->getCheckInDate()->format('Y-m-d'),
            'energy_level' => $c->getEnergyLevel(),
            'sleep_quality' => $c->getSleepQuality(),
            'soreness_level' => $c->getSorenessLevel(),
            'diet_adherence' => $c->getDietAdherence(),
            'notes' => $c->getNotes(),
            'trainer_feedback' => $c->getTrainerFeedback(),
            'status' => $c->getStatus(),
            'reviewed_at' => $c->getReviewedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
