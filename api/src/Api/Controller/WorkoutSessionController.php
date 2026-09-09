<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\TenantContext;
use App\Application\Service\WorkoutSessionService;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\WorkoutSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class WorkoutSessionController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly WorkoutSessionService $sessionService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly WorkoutSessionRepository $sessionRepository
    ) {
    }

    #[Route('/api/workout-sessions', name: 'api_workout_sessions_start', methods: ['POST'])]
    public function start(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_SESSIONS);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $role = $this->tenantContext->getRole();
        if ($role === TenantRole::STUDENT) {
            $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
            if ($student === null) {
                throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
            }
        } else {
            $studentId = trim((string) ($data['student_id'] ?? ''));
            if ($studentId === '') {
                throw new BadRequestHttpException('O campo student_id é obrigatório para treinadores/admins.');
            }
            $student = $this->studentRepository->findByIdAndTenant($studentId, $tenant);
            if ($student === null) {
                throw new NotFoundHttpException('Aluno não encontrado.');
            }
            $this->authorizationService->assertCanAccessStudent($user, $student);
        }

        $workoutDayId = isset($data['workout_day_id']) ? trim((string) $data['workout_day_id']) : null;

        $session = $this->sessionService->startSession(
            tenant: $tenant,
            student: $student,
            workoutDayId: $workoutDayId
        );

        $detail = $this->sessionService->getById($session->getId(), $tenant);

        return new JsonResponse($detail, Response::HTTP_CREATED);
    }

    #[Route('/api/workout-sessions/{id}', name: 'api_workout_sessions_detail', methods: ['GET'])]
    public function detail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_SESSIONS);

        $session = $this->sessionRepository->findByIdAndTenant($id, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $session->getStudent());

        $detail = $this->sessionService->getById($id, $tenant);

        return new JsonResponse($detail);
    }

    #[Route('/api/workout-sessions/{id}/exercises/{exerciseExecutionId}/sets', name: 'api_workout_sessions_record_set', methods: ['POST'])]
    public function recordSet(
        string $id,
        string $exerciseExecutionId,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_SESSIONS);

        $session = $this->sessionRepository->findByIdAndTenant($id, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $session->getStudent());

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $setNumber = (int) ($data['set_number'] ?? 1);
        $repsPerformed = isset($data['reps_performed']) ? (int) $data['reps_performed'] : null;
        $weightPerformed = isset($data['weight_performed']) ? (float) $data['weight_performed'] : null;
        $rpe = isset($data['rpe']) ? (float) $data['rpe'] : null;
        $restSeconds = isset($data['rest_seconds']) ? (int) $data['rest_seconds'] : null;
        $completed = (bool) ($data['completed'] ?? true);
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $set = $this->sessionService->recordSetExecution(
            tenant: $tenant,
            sessionId: $id,
            exerciseExecutionId: $exerciseExecutionId,
            setNumber: $setNumber,
            repsPerformed: $repsPerformed,
            weightPerformed: $weightPerformed,
            rpe: $rpe,
            restSeconds: $restSeconds,
            completed: $completed,
            notes: $notes
        );

        return new JsonResponse([
            'id' => $set->getId(),
            'set_number' => $set->getSetNumber(),
            'reps_performed' => $set->getRepsPerformed(),
            'weight_performed' => $set->getWeightPerformed(),
            'rpe' => $set->getRpe(),
            'rest_seconds' => $set->getRestSeconds(),
            'completed' => $set->isCompleted(),
            'notes' => $set->getNotes(),
        ]);
    }

    #[Route('/api/workout-sessions/{id}/complete', name: 'api_workout_sessions_complete', methods: ['POST'])]
    public function complete(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_SESSIONS);

        $session = $this->sessionRepository->findByIdAndTenant($id, $tenant);
        if ($session === null) {
            throw new NotFoundHttpException('Sessão de treino não encontrada.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $session->getStudent());

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $feedbackNotes = isset($data['feedback_notes']) ? trim((string) $data['feedback_notes']) : null;

        $completedSession = $this->sessionService->completeSession($tenant, $id, $feedbackNotes);

        return new JsonResponse($this->sessionService->formatSessionDetail($completedSession));
    }

    #[Route('/api/students/{id}/workout-sessions', name: 'api_student_workout_sessions_history', methods: ['GET'])]
    public function studentHistory(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_SESSIONS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $period = $request->query->get('period');
        $status = $request->query->get('status');

        $history = $this->sessionService->getHistory($tenant, $student, $period, $status);

        return new JsonResponse(['history' => $history]);
    }

    #[Route('/api/me/workout-history', name: 'api_me_workout_history', methods: ['GET'])]
    public function meHistory(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $period = $request->query->get('period');
        $status = $request->query->get('status');

        $history = $this->sessionService->getHistory($tenant, $student, $period, $status);

        return new JsonResponse(['history' => $history]);
    }
}
