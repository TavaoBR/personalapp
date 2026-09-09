<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\AssessmentMetric;
use App\Domain\Entity\MetricDefinition;
use App\Domain\Entity\PhysicalAssessment;
use App\Domain\Entity\StudentMetricRecord;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TrainerProfile;
use App\Infrastructure\Repository\MetricDefinitionRepository;
use App\Infrastructure\Repository\PhysicalAssessmentRepository;
use App\Infrastructure\Repository\StudentMetricRecordRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssessmentService
{
    public function __construct(
        private readonly MetricDefinitionRepository $metricDefRepository,
        private readonly PhysicalAssessmentRepository $assessmentRepository,
        private readonly StudentMetricRecordRepository $metricRecordRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function listAvailableMetrics(Tenant $tenant): array
    {
        $metrics = $this->metricDefRepository->findAllAvailable($tenant);
        $results = [];

        foreach ($metrics as $m) {
            $results[] = [
                'id' => $m->getId(),
                'name' => $m->getName(),
                'code' => $m->getCode(),
                'unit' => $m->getUnit(),
                'category' => $m->getCategory(),
                'is_custom' => $m->getTenant() !== null,
                'status' => $m->getStatus(),
            ];
        }

        return $results;
    }

    public function createCustomMetric(
        Tenant $tenant,
        string $name,
        string $code,
        string $unit,
        string $category = 'CUSTOM'
    ): MetricDefinition {
        $metric = new MetricDefinition(
            name: $name,
            code: $code,
            unit: $unit,
            category: $category,
            tenant: $tenant
        );

        $this->metricDefRepository->save($metric, true);

        return $metric;
    }

    /**
     * @param array<int, array<string, mixed>> $metricsData
     */
    public function createAssessment(
        Tenant $tenant,
        StudentProfile $student,
        ?TrainerProfile $trainer,
        ?DateTimeImmutable $date = null,
        ?string $notes = null,
        array $metricsData = []
    ): PhysicalAssessment {
        $assessmentDate = $date ?? new DateTimeImmutable('today');
        $assessment = new PhysicalAssessment(
            tenant: $tenant,
            student: $student,
            date: $assessmentDate,
            trainer: $trainer,
            notes: $notes
        );
        $this->assessmentRepository->save($assessment, false);

        foreach ($metricsData as $item) {
            $metricIdOrCode = (string) ($item['metric_id'] ?? $item['metric_code'] ?? '');
            if ($metricIdOrCode === '') {
                continue;
            }

            $def = $this->metricDefRepository->findByIdOrCode($metricIdOrCode, $tenant);
            if ($def === null) {
                continue;
            }

            $numericValue = isset($item['value']) ? (float) $item['value'] : null;
            $textValue = isset($item['text_value']) ? (string) $item['text_value'] : null;
            $metricNotes = isset($item['notes']) ? (string) $item['notes'] : null;

            $assessmentMetric = new AssessmentMetric(
                physicalAssessment: $assessment,
                metricDefinition: $def,
                numericValue: $numericValue,
                textValue: $textValue,
                notes: $metricNotes
            );
            $this->entityManager->persist($assessmentMetric);
            $assessment->addMetric($assessmentMetric);

            // Alimenta histórico contínuo da métrica para gráficos
            if ($numericValue !== null) {
                $record = new StudentMetricRecord(
                    tenant: $tenant,
                    student: $student,
                    metricDefinition: $def,
                    measuredAt: $assessmentDate,
                    numericValue: $numericValue,
                    textValue: $textValue,
                    source: 'ASSESSMENT',
                    physicalAssessment: $assessment,
                    notes: $metricNotes
                );
                $this->entityManager->persist($record);
            }
        }

        $this->entityManager->flush();

        return $assessment;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAssessmentById(string $id, Tenant $tenant): array
    {
        $assessment = $this->assessmentRepository->findByIdAndTenant($id, $tenant);
        if ($assessment === null) {
            throw new NotFoundHttpException('Avaliação física não encontrada.');
        }

        return $this->formatAssessment($assessment);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function listAssessmentsByStudent(Tenant $tenant, StudentProfile $student): array
    {
        $assessments = $this->assessmentRepository->findByStudent($student);
        $results = [];

        foreach ($assessments as $a) {
            $results[] = [
                'id' => $a->getId(),
                'date' => $a->getDate()->format('Y-m-d'),
                'trainer_name' => $a->getTrainer()?->getUser()->getName(),
                'metrics_count' => count($a->getMetrics()),
                'notes' => $a->getNotes(),
                'status' => $a->getStatus(),
                'created_at' => $a->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $results;
    }

    public function recordStudentMetric(
        Tenant $tenant,
        StudentProfile $student,
        string $metricIdOrCode,
        float $numericValue,
        ?DateTimeImmutable $measuredAt = null,
        string $source = 'STUDENT',
        ?string $notes = null
    ): StudentMetricRecord {
        $def = $this->metricDefRepository->findByIdOrCode($metricIdOrCode, $tenant);
        if ($def === null) {
            throw new NotFoundHttpException('Definição de métrica não encontrada.');
        }

        $record = new StudentMetricRecord(
            tenant: $tenant,
            student: $student,
            metricDefinition: $def,
            measuredAt: $measuredAt ?? new DateTimeImmutable(),
            numericValue: $numericValue,
            source: $source,
            notes: $notes
        );

        $this->metricRecordRepository->save($record, true);

        return $record;
    }

    /**
     * Retorna pontos temporais e estatísticas consolidadas para geração de gráficos.
     *
     * @return array<string, mixed>
     */
    public function getProgress(Tenant $tenant, StudentProfile $student, ?string $metricCode = null): array
    {
        // Padrão para 'WEIGHT' se não especificado
        $code = $metricCode !== null && trim($metricCode) !== '' ? strtoupper(trim($metricCode)) : 'WEIGHT';
        $def = $this->metricDefRepository->findByIdOrCode($code, $tenant);

        if ($def === null) {
            return [
                'metric' => null,
                'data_points' => [],
                'stats' => null,
            ];
        }

        $records = $this->metricRecordRepository->findByStudentAndMetric($student, $def);

        $points = [];
        $values = [];

        foreach ($records as $r) {
            if ($r->getNumericValue() !== null) {
                $val = $r->getNumericValue();
                $points[] = [
                    'date' => $r->getMeasuredAt()->format('Y-m-d'),
                    'value' => $val,
                    'source' => $r->getSource(),
                ];
                $values[] = $val;
            }
        }

        $stats = null;
        if (!empty($values)) {
            $initial = $values[0];
            $latest = $values[count($values) - 1];
            $min = min($values);
            $max = max($values);
            $change = $latest - $initial;
            $percentage = $initial != 0.0 ? round(($change / $initial) * 100, 2) : 0.0;

            $stats = [
                'initial' => $initial,
                'latest' => $latest,
                'min' => $min,
                'max' => $max,
                'absolute_change' => round($change, 2),
                'percentage_change' => $percentage,
            ];
        }

        return [
            'metric' => [
                'id' => $def->getId(),
                'name' => $def->getName(),
                'code' => $def->getCode(),
                'unit' => $def->getUnit(),
            ],
            'data_points' => $points,
            'stats' => $stats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAssessment(PhysicalAssessment $assessment): array
    {
        $metrics = [];
        foreach ($assessment->getMetrics() as $m) {
            $def = $m->getMetricDefinition();
            $metrics[] = [
                'metric_id' => $def->getId(),
                'name' => $def->getName(),
                'code' => $def->getCode(),
                'unit' => $def->getUnit(),
                'category' => $def->getCategory(),
                'numeric_value' => $m->getNumericValue(),
                'text_value' => $m->getTextValue(),
                'notes' => $m->getNotes(),
            ];
        }

        return [
            'id' => $assessment->getId(),
            'student_id' => $assessment->getStudent()->getId(),
            'trainer_id' => $assessment->getTrainer()?->getId(),
            'trainer_name' => $assessment->getTrainer()?->getUser()->getName(),
            'date' => $assessment->getDate()->format('Y-m-d'),
            'notes' => $assessment->getNotes(),
            'status' => $assessment->getStatus(),
            'created_at' => $assessment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'metrics' => $metrics,
        ];
    }
}
