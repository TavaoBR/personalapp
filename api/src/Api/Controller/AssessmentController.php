<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\AssessmentService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\PhysicalAssessmentRepository;
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

final class AssessmentController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly AssessmentService $assessmentService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerRepository,
        private readonly PhysicalAssessmentRepository $assessmentRepository
    ) {
    }

    #[Route('/api/metrics/definitions', name: 'api_metrics_definitions_list', methods: ['GET'])]
    public function listDefinitions(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_METRICS);

        $metrics = $this->assessmentService->listAvailableMetrics($tenant);

        return new JsonResponse(['metrics' => $metrics]);
    }

    #[Route('/api/metrics/definitions', name: 'api_metrics_definitions_create', methods: ['POST'])]
    public function createDefinition(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_METRICS);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'CUSTOM'));

        if ($name === '' || $code === '' || $unit === '') {
            throw new BadRequestHttpException('Nome, código e unidade são obrigatórios.');
        }

        $metric = $this->assessmentService->createCustomMetric($tenant, $name, $code, $unit, $category);

        return new JsonResponse([
            'id' => $metric->getId(),
            'name' => $metric->getName(),
            'code' => $metric->getCode(),
            'unit' => $metric->getUnit(),
            'category' => $metric->getCategory(),
            'is_custom' => true,
            'status' => $metric->getStatus(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/students/{id}/assessments', name: 'api_student_assessments_list', methods: ['GET'])]
    public function listAssessments(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ASSESSMENTS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $assessments = $this->assessmentService->listAssessmentsByStudent($tenant, $student);

        return new JsonResponse(['assessments' => $assessments]);
    }

    #[Route('/api/students/{id}/assessments', name: 'api_student_assessments_create', methods: ['POST'])]
    public function createAssessment(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_ASSESSMENTS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $trainer = $this->trainerRepository->findByUserAndTenant($user, $tenant);
        $date = isset($data['date']) ? new DateTimeImmutable((string) $data['date']) : null;
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;
        $metricsData = (array) ($data['metrics'] ?? []);

        $assessment = $this->assessmentService->createAssessment(
            tenant: $tenant,
            student: $student,
            trainer: $trainer,
            date: $date,
            notes: $notes,
            metricsData: $metricsData
        );

        $detail = $this->assessmentService->getAssessmentById($assessment->getId(), $tenant);

        return new JsonResponse($detail, Response::HTTP_CREATED);
    }

    #[Route('/api/assessments/{id}', name: 'api_assessments_detail', methods: ['GET'])]
    public function assessmentDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_ASSESSMENTS);

        $assessment = $this->assessmentRepository->findByIdAndTenant($id, $tenant);
        if ($assessment === null) {
            throw new NotFoundHttpException('Avaliação física não encontrada.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $assessment->getStudent());

        $detail = $this->assessmentService->getAssessmentById($id, $tenant);

        return new JsonResponse($detail);
    }

    #[Route('/api/students/{id}/metrics', name: 'api_student_metrics_record', methods: ['POST'])]
    public function recordMetric(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_METRICS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $metricIdOrCode = trim((string) ($data['metric_code'] ?? $data['metric_id'] ?? ''));
        if ($metricIdOrCode === '' || !isset($data['value'])) {
            throw new BadRequestHttpException('Métrica e valor numérico são obrigatórios.');
        }

        $value = (float) $data['value'];
        $date = isset($data['date']) ? new DateTimeImmutable((string) $data['date']) : new DateTimeImmutable();
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $role = $this->tenantContext->getRole();
        $source = $role === TenantRole::STUDENT ? 'STUDENT' : 'TRAINER';

        $record = $this->assessmentService->recordStudentMetric(
            tenant: $tenant,
            student: $student,
            metricIdOrCode: $metricIdOrCode,
            numericValue: $value,
            measuredAt: $date,
            source: $source,
            notes: $notes
        );

        return new JsonResponse([
            'id' => $record->getId(),
            'metric_code' => $record->getMetricDefinition()->getCode(),
            'value' => $record->getNumericValue(),
            'unit' => $record->getMetricDefinition()->getUnit(),
            'measured_at' => $record->getMeasuredAt()->format(\DateTimeInterface::ATOM),
            'source' => $record->getSource(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/students/{id}/progress', name: 'api_student_progress', methods: ['GET'])]
    public function studentProgress(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_METRICS);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $metricCode = $request->query->get('metric');
        $progress = $this->assessmentService->getProgress($tenant, $student, $metricCode);

        return new JsonResponse($progress);
    }

    #[Route('/api/me/assessments', name: 'api_me_assessments', methods: ['GET'])]
    public function meAssessments(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $assessments = $this->assessmentService->listAssessmentsByStudent($tenant, $student);

        return new JsonResponse(['assessments' => $assessments]);
    }

    #[Route('/api/me/progress', name: 'api_me_progress', methods: ['GET'])]
    public function meProgress(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $metricCode = $request->query->get('metric');
        $progress = $this->assessmentService->getProgress($tenant, $student, $metricCode);

        return new JsonResponse($progress);
    }
}
