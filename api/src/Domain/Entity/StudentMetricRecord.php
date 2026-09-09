<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentMetricRecordRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentMetricRecordRepository::class)]
#[ORM\Table(name: 'student_metric_records')]
#[ORM\Index(name: 'idx_metric_rec_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_metric_rec_student_date', columns: ['student_profile_id', 'measured_at'])]
#[ORM\Index(name: 'idx_metric_rec_student_def', columns: ['student_profile_id', 'metric_definition_id'])]
class StudentMetricRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: StudentProfile::class)]
    #[ORM\JoinColumn(name: 'student_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentProfile $student;

    #[ORM\ManyToOne(targetEntity: MetricDefinition::class)]
    #[ORM\JoinColumn(name: 'metric_definition_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private MetricDefinition $metricDefinition;

    #[ORM\ManyToOne(targetEntity: PhysicalAssessment::class)]
    #[ORM\JoinColumn(name: 'physical_assessment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PhysicalAssessment $physicalAssessment = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $measuredAt;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $numericValue = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $source; // 'STUDENT', 'TRAINER', 'ASSESSMENT'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        MetricDefinition $metricDefinition,
        DateTimeImmutable $measuredAt,
        ?float $numericValue = null,
        ?string $textValue = null,
        string $source = 'STUDENT',
        ?PhysicalAssessment $physicalAssessment = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->metricDefinition = $metricDefinition;
        $this->measuredAt = $measuredAt;
        $this->numericValue = $numericValue;
        $this->textValue = $textValue;
        $this->source = $source;
        $this->physicalAssessment = $physicalAssessment;
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getStudent(): StudentProfile
    {
        return $this->student;
    }

    public function getMetricDefinition(): MetricDefinition
    {
        return $this->metricDefinition;
    }

    public function getPhysicalAssessment(): ?PhysicalAssessment
    {
        return $this->physicalAssessment;
    }

    public function getMeasuredAt(): DateTimeImmutable
    {
        return $this->measuredAt;
    }

    public function getNumericValue(): ?float
    {
        return $this->numericValue;
    }

    public function setNumericValue(?float $numericValue): self
    {
        $this->numericValue = $numericValue;
        return $this;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function setTextValue(?string $textValue): self
    {
        $this->textValue = $textValue;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
