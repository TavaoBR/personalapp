<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\AnalyticsService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly AnalyticsService $analyticsService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerRepository
    ) {
    }

    #[Route('/api/students/{id}/analytics', name: 'api_student_analytics', methods: ['GET'])]
    public function studentAnalytics(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANALYTICS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $startA = $request->query->get('start_a') ? new DateTimeImmutable((string) $request->query->get('start_a')) : null;
        $endA = $request->query->get('end_a') ? new DateTimeImmutable((string) $request->query->get('end_a')) : null;
        $startB = $request->query->get('start_b') ? new DateTimeImmutable((string) $request->query->get('start_b')) : null;
        $endB = $request->query->get('end_b') ? new DateTimeImmutable((string) $request->query->get('end_b')) : null;

        $analytics = $this->analyticsService->getStudentAnalytics($tenant, $student, $startA, $endA, $startB, $endB);

        return new JsonResponse($analytics);
    }

    #[Route('/api/students/{id}/prs', name: 'api_student_prs', methods: ['GET'])]
    public function studentPrs(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANALYTICS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $prs = $this->analyticsService->getPersonalRecords($tenant, $student);

        return new JsonResponse(['personal_records' => $prs]);
    }

    #[Route('/api/analytics/dashboard', name: 'api_analytics_dashboard', methods: ['GET'])]
    public function dashboard(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ANALYTICS);

        $role = $this->tenantContext->getRole();
        $trainer = null;
        if ($role === TenantRole::TRAINER) {
            $trainer = $this->trainerRepository->findByUserAndTenant($user, $tenant);
        }

        $stats = $this->analyticsService->getPortfolioDashboard($tenant, $trainer);

        return new JsonResponse(['dashboard' => $stats]);
    }

    #[Route('/api/me/analytics', name: 'api_me_analytics', methods: ['GET'])]
    public function meAnalytics(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $startA = $request->query->get('start_a') ? new DateTimeImmutable((string) $request->query->get('start_a')) : null;
        $endA = $request->query->get('end_a') ? new DateTimeImmutable((string) $request->query->get('end_a')) : null;
        $startB = $request->query->get('start_b') ? new DateTimeImmutable((string) $request->query->get('start_b')) : null;
        $endB = $request->query->get('end_b') ? new DateTimeImmutable((string) $request->query->get('end_b')) : null;

        $analytics = $this->analyticsService->getStudentAnalytics($tenant, $student, $startA, $endA, $startB, $endB);

        return new JsonResponse($analytics);
    }

    #[Route('/api/me/prs', name: 'api_me_prs', methods: ['GET'])]
    public function mePrs(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $prs = $this->analyticsService->getPersonalRecords($tenant, $student);

        return new JsonResponse(['personal_records' => $prs]);
    }
}
