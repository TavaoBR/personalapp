<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\TenantContext;
use App\Application\Service\WorkoutTemplateService;
use App\Domain\Entity\User;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\WorkoutTemplateRepository;
use App\Infrastructure\Repository\WorkoutTemplateVersionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/workout-templates', name: 'api_workout_templates_')]
final class WorkoutTemplateController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly WorkoutTemplateService $workoutTemplateService,
        private readonly WorkoutTemplateRepository $workoutTemplateRepository,
        private readonly WorkoutTemplateVersionRepository $templateVersionRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_TEMPLATES);

        $templates = $this->workoutTemplateService->list($tenant);

        return new JsonResponse(['templates' => $templates]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_TEMPLATES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException('O nome do template é obrigatório.');
        }

        $template = $this->workoutTemplateService->create(
            tenant: $tenant,
            name: $name,
            description: isset($data['description']) ? trim((string) $data['description']) : null,
            objective: isset($data['objective']) ? trim((string) $data['objective']) : null,
            level: isset($data['level']) ? trim((string) $data['level']) : null,
            notes: isset($data['notes']) ? trim((string) $data['notes']) : null,
            daysData: (array) ($data['days'] ?? [])
        );

        $detail = $this->workoutTemplateService->getById($template->getId(), $tenant);

        return new JsonResponse($detail, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_TEMPLATES);

        $detail = $this->workoutTemplateService->getById($id, $tenant);

        return new JsonResponse($detail);
    }

    #[Route('/{id}/versions', name: 'versions', methods: ['GET'])]
    public function versions(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_WORKOUT_TEMPLATES);

        $template = $this->workoutTemplateRepository->findByIdAndTenant($id, $tenant);
        if ($template === null) {
            throw new NotFoundHttpException('Template de treino não encontrado.');
        }

        $versions = $this->templateVersionRepository->findByTemplate($template);
        $result = array_map(fn ($v) => $this->workoutTemplateService->formatVersion($v), $versions);

        return new JsonResponse(['versions' => $result]);
    }

    #[Route('/{id}/versions', name: 'create_version', methods: ['POST'])]
    public function createVersion(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_TEMPLATES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $version = $this->workoutTemplateService->createVersion(
            tenant: $tenant,
            templateId: $id,
            objective: isset($data['objective']) ? trim((string) $data['objective']) : null,
            level: isset($data['level']) ? trim((string) $data['level']) : null,
            notes: isset($data['notes']) ? trim((string) $data['notes']) : null,
            daysData: (array) ($data['days'] ?? [])
        );

        return new JsonResponse($this->workoutTemplateService->formatVersion($version), Response::HTTP_CREATED);
    }

    #[Route('/{id}/assign', name: 'assign', methods: ['POST'])]
    public function assign(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_WORKOUT_TEMPLATES);

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $studentIds = (array) ($data['student_ids'] ?? []);

        if (empty($studentIds)) {
            throw new BadRequestHttpException('Informe pelo menos um ID de aluno em student_ids.');
        }

        $customName = isset($data['plan_name']) ? trim((string) $data['plan_name']) : null;

        $createdPlans = $this->workoutTemplateService->assignToStudents(
            tenant: $tenant,
            templateId: $id,
            studentIds: $studentIds,
            customName: $customName
        );

        return new JsonResponse(['assigned' => $createdPlans], Response::HTTP_CREATED);
    }
}
