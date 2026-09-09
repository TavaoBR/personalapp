<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\ExerciseService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Enum\MuscleGroup;
use App\Domain\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/exercises', name: 'api_exercises_')]
final class ExerciseController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly ExerciseService $exerciseService
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_EXERCISES);

        $query = $request->query->get('q');
        $muscleGroupParam = $request->query->get('muscle_group');
        $muscleGroup = null;

        if ($muscleGroupParam !== null && trim($muscleGroupParam) !== '') {
            $muscleGroup = MuscleGroup::tryFrom(strtoupper(trim($muscleGroupParam)));
            if ($muscleGroup === null) {
                throw new BadRequestHttpException('Grupo muscular inválido.');
            }
        }

        $results = $this->exerciseService->search($tenant, $query, $muscleGroup);

        return new JsonResponse(['exercises' => $results]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_EXERCISES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException('O nome do exercício é obrigatório.');
        }

        $muscleGroupValue = strtoupper(trim((string) ($data['muscle_group'] ?? '')));
        $muscleGroup = MuscleGroup::tryFrom($muscleGroupValue);
        if ($muscleGroup === null) {
            throw new BadRequestHttpException('Grupo muscular inválido.');
        }

        $equipment = isset($data['equipment']) ? trim((string) $data['equipment']) : null;
        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $videoUrl = isset($data['video_url']) ? trim((string) $data['video_url']) : null;
        $globalExerciseId = isset($data['global_exercise_id']) ? trim((string) $data['global_exercise_id']) : null;

        $exercise = $this->exerciseService->createTenantExercise(
            tenant: $tenant,
            name: $name,
            muscleGroup: $muscleGroup,
            equipment: $equipment,
            description: $description,
            videoUrl: $videoUrl,
            globalExerciseId: $globalExerciseId
        );

        return new JsonResponse([
            'id' => $exercise->getId(),
            'name' => $exercise->getName(),
            'muscle_group' => $exercise->getMuscleGroup()->value,
            'equipment' => $exercise->getEquipment(),
            'description' => $exercise->getDescription(),
            'video_url' => $exercise->getVideoUrl(),
            'origin' => 'TENANT',
            'is_custom' => true,
            'global_exercise_id' => $exercise->getGlobalExercise()?->getId(),
            'status' => $exercise->getStatus(),
            'version' => 1,
            'created_at' => $exercise->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_EXERCISES);

        $exercise = $this->exerciseService->getById($id, $tenant);

        return new JsonResponse($exercise);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_EXERCISES);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = isset($data['name']) ? trim((string) $data['name']) : null;
        $muscleGroup = null;
        if (isset($data['muscle_group'])) {
            $muscleGroup = MuscleGroup::tryFrom(strtoupper(trim((string) $data['muscle_group'])));
            if ($muscleGroup === null) {
                throw new BadRequestHttpException('Grupo muscular inválido.');
            }
        }
        $equipment = array_key_exists('equipment', $data) ? ($data['equipment'] !== null ? trim((string) $data['equipment']) : null) : null;
        $description = array_key_exists('description', $data) ? ($data['description'] !== null ? trim((string) $data['description']) : null) : null;
        $videoUrl = array_key_exists('video_url', $data) ? ($data['video_url'] !== null ? trim((string) $data['video_url']) : null) : null;

        $exercise = $this->exerciseService->updateTenantExercise(
            tenant: $tenant,
            id: $id,
            name: $name,
            muscleGroup: $muscleGroup,
            equipment: $equipment,
            description: $description,
            videoUrl: $videoUrl
        );

        $detail = $this->exerciseService->getById($exercise->getId(), $tenant);

        return new JsonResponse($detail);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_EXERCISES);

        $this->exerciseService->archiveTenantExercise($tenant, $id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
