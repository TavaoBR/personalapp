<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ExerciseVersion;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\WorkoutTemplate;
use App\Domain\Entity\WorkoutTemplateDay;
use App\Domain\Entity\WorkoutTemplateExercise;
use App\Domain\Entity\WorkoutTemplateVersion;
use App\Infrastructure\Repository\ExerciseVersionRepository;
use App\Infrastructure\Repository\GlobalExerciseRepository;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TenantExerciseRepository;
use App\Infrastructure\Repository\WorkoutTemplateRepository;
use App\Infrastructure\Repository\WorkoutTemplateVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkoutTemplateService
{
    public function __construct(
        private readonly WorkoutTemplateRepository $workoutTemplateRepository,
        private readonly WorkoutTemplateVersionRepository $templateVersionRepository,
        private readonly ExerciseVersionRepository $exerciseVersionRepository,
        private readonly TenantExerciseRepository $tenantExerciseRepository,
        private readonly GlobalExerciseRepository $globalExerciseRepository,
        private readonly StudentProfileRepository $studentProfileRepository,
        private readonly StudentWorkoutPlanService $studentWorkoutPlanService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function list(Tenant $tenant): array
    {
        $templates = $this->workoutTemplateRepository->findByTenant($tenant);
        $results = [];

        foreach ($templates as $template) {
            $latest = $this->templateVersionRepository->findLatestForTemplate($template);
            $results[] = [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'status' => $template->getStatus(),
                'latest_version' => $latest?->getVersionNumber() ?? 1,
                'objective' => $latest?->getObjective(),
                'level' => $latest?->getLevel(),
                'days_count' => $latest ? count($latest->getDays()) : 0,
                'created_at' => $template->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $template->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function getById(string $id, Tenant $tenant): array
    {
        $template = $this->workoutTemplateRepository->findByIdAndTenant($id, $tenant);
        if ($template === null) {
            throw new NotFoundHttpException('Template de treino não encontrado.');
        }

        $latestVersion = $this->templateVersionRepository->findLatestForTemplate($template);

        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'status' => $template->getStatus(),
            'created_at' => $template->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'current_version' => $latestVersion !== null ? $this->formatVersion($latestVersion) : null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    public function create(
        Tenant $tenant,
        string $name,
        ?string $description = null,
        ?string $objective = null,
        ?string $level = null,
        ?string $notes = null,
        array $daysData = []
    ): WorkoutTemplate {
        $template = new WorkoutTemplate($tenant, $name, $description);
        $this->workoutTemplateRepository->save($template, false);

        $version = new WorkoutTemplateVersion(
            workoutTemplate: $template,
            versionNumber: 1,
            status: 'PUBLISHED',
            objective: $objective,
            level: $level,
            notes: $notes
        );
        $this->templateVersionRepository->save($version, false);

        $this->populateDaysAndExercises($version, $daysData, $tenant);

        $this->entityManager->flush();

        return $template;
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    public function createVersion(
        Tenant $tenant,
        string $templateId,
        ?string $objective = null,
        ?string $level = null,
        ?string $notes = null,
        array $daysData = []
    ): WorkoutTemplateVersion {
        $template = $this->workoutTemplateRepository->findByIdAndTenant($templateId, $tenant);
        if ($template === null) {
            throw new NotFoundHttpException('Template de treino não encontrado.');
        }

        $latest = $this->templateVersionRepository->findLatestForTemplate($template);
        $nextNumber = $latest !== null ? $latest->getVersionNumber() + 1 : 2;

        $newVersion = new WorkoutTemplateVersion(
            workoutTemplate: $template,
            versionNumber: $nextNumber,
            status: 'PUBLISHED',
            objective: $objective ?? $latest?->getObjective(),
            level: $level ?? $latest?->getLevel(),
            notes: $notes ?? $latest?->getNotes()
        );
        $this->templateVersionRepository->save($newVersion, false);

        $this->populateDaysAndExercises($newVersion, $daysData, $tenant);

        $template->touch();
        $this->entityManager->flush();

        return $newVersion;
    }

    /**
     * @param array<string> $studentIds
     * @return array<array<string, mixed>>
     */
    public function assignToStudents(Tenant $tenant, string $templateId, array $studentIds, ?string $customName = null): array
    {
        $template = $this->workoutTemplateRepository->findByIdAndTenant($templateId, $tenant);
        if ($template === null) {
            throw new NotFoundHttpException('Template de treino não encontrado.');
        }

        $plans = [];
        foreach ($studentIds as $studentId) {
            $student = $this->studentProfileRepository->findByIdAndTenant($studentId, $tenant);
            if ($student === null) {
                continue;
            }

            $plan = $this->studentWorkoutPlanService->createFromTemplate(
                tenant: $tenant,
                student: $student,
                template: $template,
                customPlanName: $customName
            );

            $plans[] = [
                'student_id' => $student->getId(),
                'plan_id' => $plan->getId(),
                'plan_name' => $plan->getName(),
            ];
        }

        return $plans;
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    private function populateDaysAndExercises(WorkoutTemplateVersion $version, array $daysData, Tenant $tenant): void
    {
        foreach ($daysData as $dayIndex => $dayItem) {
            $dayName = trim((string) ($dayItem['name'] ?? sprintf('Treino %s', chr(65 + $dayIndex))));
            $day = new WorkoutTemplateDay(
                workoutTemplateVersion: $version,
                name: $dayName,
                orderIndex: (int) ($dayItem['order_index'] ?? $dayIndex)
            );
            $this->entityManager->persist($day);
            $version->addDay($day);

            $exercisesData = (array) ($dayItem['exercises'] ?? []);
            foreach ($exercisesData as $exIndex => $exItem) {
                $exerciseVersion = $this->resolveExerciseVersion($exItem, $tenant);
                if ($exerciseVersion === null) {
                    continue;
                }

                $templateExercise = new WorkoutTemplateExercise(
                    workoutTemplateDay: $day,
                    exerciseVersion: $exerciseVersion,
                    orderIndex: (int) ($exItem['order_index'] ?? $exIndex),
                    sets: (int) ($exItem['sets'] ?? 3),
                    reps: (string) ($exItem['reps'] ?? '10-12'),
                    weight: isset($exItem['weight']) ? (string) $exItem['weight'] : null,
                    restSeconds: isset($exItem['rest_seconds']) ? (int) $exItem['rest_seconds'] : 60,
                    rpe: isset($exItem['rpe']) ? (string) $exItem['rpe'] : null,
                    technique: isset($exItem['technique']) ? (string) $exItem['technique'] : null,
                    notes: isset($exItem['notes']) ? (string) $exItem['notes'] : null
                );
                $this->entityManager->persist($templateExercise);
                $day->addExercise($templateExercise);
            }
        }
    }

    /**
     * @param array<string, mixed> $exItem
     */
    private function resolveExerciseVersion(array $exItem, Tenant $tenant): ?ExerciseVersion
    {
        // 1. Se informou diretamente exercise_version_id
        if (!empty($exItem['exercise_version_id'])) {
            $version = $this->exerciseVersionRepository->find($exItem['exercise_version_id']);
            if ($version !== null) {
                return $version;
            }
        }

        // 2. Se informou exercise_id (pode ser tenant exercise ou global exercise)
        if (!empty($exItem['exercise_id'])) {
            $exerciseId = (string) $exItem['exercise_id'];

            // Tenta achar como TenantExercise do tenant
            $tenantEx = $this->tenantExerciseRepository->findByIdAndTenant($exerciseId, $tenant);
            if ($tenantEx !== null) {
                return $this->exerciseVersionRepository->findLatestForTenantExercise($tenantEx);
            }

            // Tenta achar como GlobalExercise
            $globalEx = $this->globalExerciseRepository->find($exerciseId);
            if ($globalEx !== null && $globalEx->getStatus() === 'ACTIVE') {
                return $this->exerciseVersionRepository->findLatestForGlobalExercise($globalEx);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatVersion(WorkoutTemplateVersion $version): array
    {
        $days = [];
        foreach ($version->getDays() as $day) {
            $exercises = [];
            foreach ($day->getExercises() as $ex) {
                $ver = $ex->getExerciseVersion();
                $exercises[] = [
                    'id' => $ex->getId(),
                    'exercise_version_id' => $ver->getId(),
                    'exercise_name' => $ver->getName(),
                    'muscle_group' => $ver->getMuscleGroup()->value,
                    'equipment' => $ver->getEquipment(),
                    'order_index' => $ex->getOrderIndex(),
                    'sets' => $ex->getSets(),
                    'reps' => $ex->getReps(),
                    'weight' => $ex->getWeight(),
                    'rest_seconds' => $ex->getRestSeconds(),
                    'rpe' => $ex->getRpe(),
                    'technique' => $ex->getTechnique(),
                    'notes' => $ex->getNotes(),
                ];
            }

            $days[] = [
                'id' => $day->getId(),
                'name' => $day->getName(),
                'order_index' => $day->getOrderIndex(),
                'exercises' => $exercises,
            ];
        }

        return [
            'id' => $version->getId(),
            'version_number' => $version->getVersionNumber(),
            'status' => $version->getStatus(),
            'objective' => $version->getObjective(),
            'level' => $version->getLevel(),
            'notes' => $version->getNotes(),
            'created_at' => $version->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'published_at' => $version->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'days' => $days,
        ];
    }
}
