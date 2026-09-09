<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ExerciseVersion;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\StudentWorkoutDay;
use App\Domain\Entity\StudentWorkoutExercise;
use App\Domain\Entity\StudentWorkoutPlan;
use App\Domain\Entity\StudentWorkoutPlanVersion;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\WorkoutTemplate;
use App\Domain\Entity\WorkoutTemplateVersion;
use App\Infrastructure\Repository\ExerciseVersionRepository;
use App\Infrastructure\Repository\GlobalExerciseRepository;
use App\Infrastructure\Repository\StudentWorkoutPlanRepository;
use App\Infrastructure\Repository\StudentWorkoutPlanVersionRepository;
use App\Infrastructure\Repository\TenantExerciseRepository;
use App\Infrastructure\Repository\WorkoutTemplateVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StudentWorkoutPlanService
{
    public function __construct(
        private readonly StudentWorkoutPlanRepository $planRepository,
        private readonly StudentWorkoutPlanVersionRepository $planVersionRepository,
        private readonly WorkoutTemplateVersionRepository $templateVersionRepository,
        private readonly ExerciseVersionRepository $exerciseVersionRepository,
        private readonly TenantExerciseRepository $tenantExerciseRepository,
        private readonly GlobalExerciseRepository $globalExerciseRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function listForStudent(Tenant $tenant, StudentProfile $student): array
    {
        $plans = $this->planRepository->findByStudent($student);
        $results = [];

        foreach ($plans as $plan) {
            $latest = $this->planVersionRepository->findLatestForPlan($plan);
            $results[] = [
                'id' => $plan->getId(),
                'name' => $plan->getName(),
                'source_template_id' => $plan->getSourceTemplate()?->getId(),
                'status' => $plan->getStatus(),
                'latest_version' => $latest?->getVersionNumber() ?? 1,
                'days_count' => $latest ? count($latest->getDays()) : 0,
                'created_at' => $plan->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $plan->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActivePlan(Tenant $tenant, StudentProfile $student): ?array
    {
        $plan = $this->planRepository->findActiveForStudent($student);
        if ($plan === null) {
            return null;
        }

        return $this->getById($plan->getId(), $tenant);
    }

    /**
     * @return array<string, mixed>
     */
    public function getById(string $id, Tenant $tenant): array
    {
        $plan = $this->planRepository->findByIdAndTenant($id, $tenant);
        if ($plan === null) {
            throw new NotFoundHttpException('Ficha de treino não encontrada.');
        }

        $latestVersion = $this->planVersionRepository->findLatestForPlan($plan);

        return [
            'id' => $plan->getId(),
            'name' => $plan->getName(),
            'student_id' => $plan->getStudent()->getId(),
            'source_template_id' => $plan->getSourceTemplate()?->getId(),
            'status' => $plan->getStatus(),
            'created_at' => $plan->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'current_version' => $latestVersion !== null ? $this->formatVersion($latestVersion) : null,
        ];
    }

    public function createFromTemplate(
        Tenant $tenant,
        StudentProfile $student,
        WorkoutTemplate $template,
        ?string $customPlanName = null
    ): StudentWorkoutPlan {
        $templateVersion = $this->templateVersionRepository->findLatestForTemplate($template);

        // Desativa planos ativos anteriores do aluno
        $activePlan = $this->planRepository->findActiveForStudent($student);
        if ($activePlan !== null) {
            $activePlan->setStatus('ARCHIVED');
        }

        $planName = $customPlanName !== null && trim($customPlanName) !== '' ? trim($customPlanName) : $template->getName();
        $plan = new StudentWorkoutPlan($tenant, $student, $planName, $template);
        $this->planRepository->save($plan, false);

        $planVersion = new StudentWorkoutPlanVersion(
            studentWorkoutPlan: $plan,
            versionNumber: 1,
            status: 'ACTIVE',
            sourceTemplateVersion: $templateVersion,
            notes: $templateVersion?->getNotes()
        );
        $this->planVersionRepository->save($planVersion, false);
        $plan->addVersion($planVersion);

        // Cópia profunda (deep clone) dos dias e exercícios do template
        if ($templateVersion !== null) {
            foreach ($templateVersion->getDays() as $tDay) {
                $studentDay = new StudentWorkoutDay(
                    studentWorkoutPlanVersion: $planVersion,
                    name: $tDay->getName(),
                    orderIndex: $tDay->getOrderIndex()
                );
                $this->entityManager->persist($studentDay);
                $planVersion->addDay($studentDay);

                foreach ($tDay->getExercises() as $tExercise) {
                    $studentExercise = new StudentWorkoutExercise(
                        studentWorkoutDay: $studentDay,
                        exerciseVersion: $tExercise->getExerciseVersion(),
                        orderIndex: $tExercise->getOrderIndex(),
                        sets: $tExercise->getSets(),
                        reps: $tExercise->getReps(),
                        weight: $tExercise->getWeight(),
                        restSeconds: $tExercise->getRestSeconds(),
                        rpe: $tExercise->getRpe(),
                        technique: $tExercise->getTechnique(),
                        notes: $tExercise->getNotes()
                    );
                    $this->entityManager->persist($studentExercise);
                    $studentDay->addExercise($studentExercise);
                }
            }
        }

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    public function createCustomPlan(
        Tenant $tenant,
        StudentProfile $student,
        string $name,
        ?string $notes = null,
        array $daysData = []
    ): StudentWorkoutPlan {
        $activePlan = $this->planRepository->findActiveForStudent($student);
        if ($activePlan !== null) {
            $activePlan->setStatus('ARCHIVED');
        }

        $plan = new StudentWorkoutPlan($tenant, $student, $name, null);
        $this->planRepository->save($plan, false);

        $planVersion = new StudentWorkoutPlanVersion(
            studentWorkoutPlan: $plan,
            versionNumber: 1,
            status: 'ACTIVE',
            sourceTemplateVersion: null,
            notes: $notes
        );
        $this->planVersionRepository->save($planVersion, false);
        $plan->addVersion($planVersion);

        $this->populateDaysAndExercises($planVersion, $daysData, $tenant);

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    public function createVersion(
        Tenant $tenant,
        string $planId,
        ?string $notes = null,
        array $daysData = []
    ): StudentWorkoutPlanVersion {
        $plan = $this->planRepository->findByIdAndTenant($planId, $tenant);
        if ($plan === null) {
            throw new NotFoundHttpException('Ficha de treino não encontrada.');
        }

        $latest = $this->planVersionRepository->findLatestForPlan($plan);
        $nextNumber = $latest !== null ? $latest->getVersionNumber() + 1 : 2;

        $newVersion = new StudentWorkoutPlanVersion(
            studentWorkoutPlan: $plan,
            versionNumber: $nextNumber,
            status: 'ACTIVE',
            sourceTemplateVersion: $latest?->getSourceTemplateVersion(),
            notes: $notes ?? $latest?->getNotes()
        );
        $this->planVersionRepository->save($newVersion, false);
        $plan->addVersion($newVersion);

        $this->populateDaysAndExercises($newVersion, $daysData, $tenant);

        $plan->touch();
        $this->entityManager->flush();

        return $newVersion;
    }

    public function activatePlan(Tenant $tenant, string $planId): void
    {
        $plan = $this->planRepository->findByIdAndTenant($planId, $tenant);
        if ($plan === null) {
            throw new NotFoundHttpException('Ficha de treino não encontrada.');
        }

        $activePlan = $this->planRepository->findActiveForStudent($plan->getStudent());
        if ($activePlan !== null && $activePlan->getId() !== $plan->getId()) {
            $activePlan->setStatus('ARCHIVED');
        }

        $plan->setStatus('ACTIVE');
        $this->planRepository->save($plan, true);
    }

    public function archivePlan(Tenant $tenant, string $planId): void
    {
        $plan = $this->planRepository->findByIdAndTenant($planId, $tenant);
        if ($plan === null) {
            throw new NotFoundHttpException('Ficha de treino não encontrada.');
        }

        $plan->setStatus('ARCHIVED');
        $this->planRepository->save($plan, true);
    }

    /**
     * @param array<int, array<string, mixed>> $daysData
     */
    private function populateDaysAndExercises(StudentWorkoutPlanVersion $version, array $daysData, Tenant $tenant): void
    {
        foreach ($daysData as $dayIndex => $dayItem) {
            $dayName = trim((string) ($dayItem['name'] ?? sprintf('Treino %s', chr(65 + $dayIndex))));
            $day = new StudentWorkoutDay(
                studentWorkoutPlanVersion: $version,
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

                $studentExercise = new StudentWorkoutExercise(
                    studentWorkoutDay: $day,
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
                $this->entityManager->persist($studentExercise);
                $day->addExercise($studentExercise);
            }
        }
    }

    /**
     * @param array<string, mixed> $exItem
     */
    private function resolveExerciseVersion(array $exItem, Tenant $tenant): ?ExerciseVersion
    {
        if (!empty($exItem['exercise_version_id'])) {
            $version = $this->exerciseVersionRepository->find($exItem['exercise_version_id']);
            if ($version !== null) {
                return $version;
            }
        }

        if (!empty($exItem['exercise_id'])) {
            $exerciseId = (string) $exItem['exercise_id'];

            $tenantEx = $this->tenantExerciseRepository->findByIdAndTenant($exerciseId, $tenant);
            if ($tenantEx !== null) {
                return $this->exerciseVersionRepository->findLatestForTenantExercise($tenantEx);
            }

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
    public function formatVersion(StudentWorkoutPlanVersion $version): array
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
            'source_template_version_id' => $version->getSourceTemplateVersion()?->getId(),
            'notes' => $version->getNotes(),
            'valid_from' => $version->getValidFrom()?->format(\DateTimeInterface::ATOM),
            'valid_until' => $version->getValidUntil()?->format(\DateTimeInterface::ATOM),
            'created_at' => $version->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'days' => $days,
        ];
    }
}
