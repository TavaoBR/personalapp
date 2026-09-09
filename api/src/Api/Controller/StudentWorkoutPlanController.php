<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\StudentWorkoutPlanService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class StudentWorkoutPlanController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly StudentWorkoutPlanService $workoutPlanService,
        private readonly StudentProfileRepository $studentRepository
    ) {
    }

    #[Route('/api/students/{id}/workout-plans', name: 'api_student_workout_plans_list', methods: ['GET'])]
    public function listByStudent(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_PLANS);

        $plans = $this->workoutPlanService->listForStudent($tenant, $student);

        return new JsonResponse(['plans' => $plans]);
    }

    #[Route('/api/students/{id}/workout-plans', name: 'api_student_workout_plans_create', methods: ['POST'])]
    public function createForStudent(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_PLANS);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException('O nome da ficha é obrigatório.');
        }

        $plan = $this->workoutPlanService->createCustomPlan(
            tenant: $tenant,
            student: $student,
            name: $name,
            notes: isset($data['notes']) ? trim((string) $data['notes']) : null,
            daysData: (array) ($data['days'] ?? [])
        );

        $detail = $this->workoutPlanService->getById($plan->getId(), $tenant);

        return new JsonResponse($detail, Response::HTTP_CREATED);
    }

    #[Route('/api/workout-plans/{id}', name: 'api_workout_plans_detail', methods: ['GET'])]
    public function detail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_PLANS);

        $detail = $this->workoutPlanService->getById($id, $tenant);

        // Validação de acesso ao aluno dono da ficha
        $student = $this->studentRepository->findByIdAndTenant($detail['student_id'], $tenant);
        if ($student !== null) {
            $this->authorizationService->assertCanAccessStudent($user, $student);
        }

        return new JsonResponse($detail);
    }

    #[Route('/api/workout-plans/{id}/versions', name: 'api_workout_plans_create_version', methods: ['POST'])]
    public function createVersion(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_PLANS);

        $detail = $this->workoutPlanService->getById($id, $tenant);
        $student = $this->studentRepository->findByIdAndTenant($detail['student_id'], $tenant);
        if ($student !== null) {
            $this->authorizationService->assertCanAccessStudent($user, $student);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $version = $this->workoutPlanService->createVersion(
            tenant: $tenant,
            planId: $id,
            notes: isset($data['notes']) ? trim((string) $data['notes']) : null,
            daysData: (array) ($data['days'] ?? [])
        );

        return new JsonResponse($this->workoutPlanService->formatVersion($version), Response::HTTP_CREATED);
    }

    #[Route('/api/workout-plans/{id}/activate', name: 'api_workout_plans_activate', methods: ['POST'])]
    public function activate(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_PLANS);

        $detail = $this->workoutPlanService->getById($id, $tenant);
        $student = $this->studentRepository->findByIdAndTenant($detail['student_id'], $tenant);
        if ($student !== null) {
            $this->authorizationService->assertCanAccessStudent($user, $student);
        }

        $this->workoutPlanService->activatePlan($tenant, $id);

        return new JsonResponse(['status' => 'ACTIVE']);
    }

    #[Route('/api/workout-plans/{id}/archive', name: 'api_workout_plans_archive', methods: ['POST'])]
    public function archive(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_PLANS);

        $detail = $this->workoutPlanService->getById($id, $tenant);
        $student = $this->studentRepository->findByIdAndTenant($detail['student_id'], $tenant);
        if ($student !== null) {
            $this->authorizationService->assertCanAccessStudent($user, $student);
        }

        $this->workoutPlanService->archivePlan($tenant, $id);

        return new JsonResponse(['status' => 'ARCHIVED']);
    }

    #[Route('/api/me/workouts', name: 'api_me_workouts', methods: ['GET'])]
    public function meWorkouts(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $activePlan = $this->workoutPlanService->getActivePlan($tenant, $student);
        if ($activePlan === null) {
            return new JsonResponse(['plan' => null]);
        }

        return new JsonResponse(['plan' => $activePlan]);
    }
}
