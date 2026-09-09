<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ExerciseVersion;
use App\Domain\Entity\GlobalExercise;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantExercise;
use App\Domain\Enum\MuscleGroup;
use App\Infrastructure\Repository\ExerciseVersionRepository;
use App\Infrastructure\Repository\GlobalExerciseRepository;
use App\Infrastructure\Repository\TenantExerciseRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExerciseService
{
    public function __construct(
        private readonly GlobalExerciseRepository $globalExerciseRepository,
        private readonly TenantExerciseRepository $tenantExerciseRepository,
        private readonly ExerciseVersionRepository $exerciseVersionRepository
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function search(Tenant $tenant, ?string $query = null, ?MuscleGroup $muscleGroup = null): array
    {
        $globals = $this->globalExerciseRepository->search($query, $muscleGroup);
        $tenantExercises = $this->tenantExerciseRepository->search($tenant, $query, $muscleGroup);

        $results = [];

        foreach ($globals as $global) {
            $results[] = [
                'id' => $global->getId(),
                'name' => $global->getName(),
                'muscle_group' => $global->getMuscleGroup()->value,
                'equipment' => $global->getEquipment(),
                'description' => $global->getDescription(),
                'video_url' => $global->getVideoUrl(),
                'origin' => 'GLOBAL',
                'is_custom' => false,
                'status' => $global->getStatus(),
            ];
        }

        foreach ($tenantExercises as $exercise) {
            $results[] = [
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
            ];
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function getById(string $id, Tenant $tenant): array
    {
        $tenantEx = $this->tenantExerciseRepository->findByIdAndTenant($id, $tenant);
        if ($tenantEx !== null) {
            $latest = $this->exerciseVersionRepository->findLatestForTenantExercise($tenantEx);
            return [
                'id' => $tenantEx->getId(),
                'name' => $tenantEx->getName(),
                'muscle_group' => $tenantEx->getMuscleGroup()->value,
                'equipment' => $tenantEx->getEquipment(),
                'description' => $tenantEx->getDescription(),
                'video_url' => $tenantEx->getVideoUrl(),
                'origin' => 'TENANT',
                'is_custom' => true,
                'global_exercise_id' => $tenantEx->getGlobalExercise()?->getId(),
                'status' => $tenantEx->getStatus(),
                'version' => $latest?->getVersionNumber() ?? 1,
            ];
        }

        $globalEx = $this->globalExerciseRepository->find($id);
        if ($globalEx !== null && $globalEx->getStatus() === 'ACTIVE') {
            $latest = $this->exerciseVersionRepository->findLatestForGlobalExercise($globalEx);
            return [
                'id' => $globalEx->getId(),
                'name' => $globalEx->getName(),
                'muscle_group' => $globalEx->getMuscleGroup()->value,
                'equipment' => $globalEx->getEquipment(),
                'description' => $globalEx->getDescription(),
                'video_url' => $globalEx->getVideoUrl(),
                'origin' => 'GLOBAL',
                'is_custom' => false,
                'status' => $globalEx->getStatus(),
                'version' => $latest?->getVersionNumber() ?? 1,
            ];
        }

        throw new NotFoundHttpException('Exercício não encontrado.');
    }

    public function createTenantExercise(
        Tenant $tenant,
        string $name,
        MuscleGroup $muscleGroup,
        ?string $equipment = null,
        ?string $description = null,
        ?string $videoUrl = null,
        ?string $globalExerciseId = null
    ): TenantExercise {
        $globalExercise = null;
        if ($globalExerciseId !== null && trim($globalExerciseId) !== '') {
            $globalExercise = $this->globalExerciseRepository->find($globalExerciseId);
        }

        $exercise = new TenantExercise(
            tenant: $tenant,
            name: $name,
            muscleGroup: $muscleGroup,
            globalExercise: $globalExercise,
            equipment: $equipment,
            description: $description,
            videoUrl: $videoUrl
        );

        $this->tenantExerciseRepository->save($exercise, false);

        $initialVersion = new ExerciseVersion(
            name: $name,
            muscleGroup: $muscleGroup,
            versionNumber: 1,
            tenantExercise: $exercise,
            globalExercise: null,
            equipment: $equipment,
            description: $description,
            videoUrl: $videoUrl
        );

        $this->exerciseVersionRepository->save($initialVersion, true);

        return $exercise;
    }

    public function updateTenantExercise(
        Tenant $tenant,
        string $id,
        ?string $name = null,
        ?MuscleGroup $muscleGroup = null,
        ?string $equipment = null,
        ?string $description = null,
        ?string $videoUrl = null
    ): TenantExercise {
        $exercise = $this->tenantExerciseRepository->findByIdAndTenant($id, $tenant);
        if ($exercise === null) {
            throw new NotFoundHttpException('Exercício não encontrado.');
        }

        if ($name !== null) {
            $exercise->setName($name);
        }
        if ($muscleGroup !== null) {
            $exercise->setMuscleGroup($muscleGroup);
        }
        if ($equipment !== null) {
            $exercise->setEquipment($equipment);
        }
        if ($description !== null) {
            $exercise->setDescription($description);
        }
        if ($videoUrl !== null) {
            $exercise->setVideoUrl($videoUrl);
        }

        $latestVersion = $this->exerciseVersionRepository->findLatestForTenantExercise($exercise);
        $nextVersionNumber = $latestVersion !== null ? $latestVersion->getVersionNumber() + 1 : 2;

        $newVersion = new ExerciseVersion(
            name: $exercise->getName(),
            muscleGroup: $exercise->getMuscleGroup(),
            versionNumber: $nextVersionNumber,
            tenantExercise: $exercise,
            globalExercise: null,
            equipment: $exercise->getEquipment(),
            description: $exercise->getDescription(),
            videoUrl: $exercise->getVideoUrl()
        );

        $this->tenantExerciseRepository->save($exercise, false);
        $this->exerciseVersionRepository->save($newVersion, true);

        return $exercise;
    }

    public function archiveTenantExercise(Tenant $tenant, string $id): void
    {
        $exercise = $this->tenantExerciseRepository->findByIdAndTenant($id, $tenant);
        if ($exercise === null) {
            throw new NotFoundHttpException('Exercício não encontrado.');
        }

        $exercise->setStatus('ARCHIVED');
        $this->tenantExerciseRepository->save($exercise, true);
    }
}
