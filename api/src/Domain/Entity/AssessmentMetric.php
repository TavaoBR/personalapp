<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assessment_metrics')]
#[ORM\Index(name: 'idx_assessment_metric_assessment', columns: ['physical_assessment_id'])]
#[ORM\Index(name: 'idx_assessment_metric_definition', columns: ['metric_definition_id'])]
class AssessmentMetric
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: PhysicalAssessment::class, inversedBy: 'metrics')]
    #[ORM\JoinColumn(name: 'physical_assessment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PhysicalAssessment $physicalAssessment;

    #[ORM\ManyToOne(targetEntity: MetricDefinition::class)]
    #[ORM\JoinColumn(name: 'metric_definition_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private MetricDefinition $metricDefinition;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $numericValue = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(
        PhysicalAssessment $physicalAssessment,
        MetricDefinition $metricDefinition,
        ?float $numericValue = null,
        ?string $textValue = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->physicalAssessment = $physicalAssessment;
        $this->metricDefinition = $metricDefinition;
        $this->numericValue = $numericValue;
        $this->textValue = $textValue;
        $this->notes = $notes;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPhysicalAssessment(): PhysicalAssessment
    {
        return $this->physicalAssessment;
    }

    public function getMetricDefinition(): MetricDefinition
    {
        return $this->metricDefinition;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}
