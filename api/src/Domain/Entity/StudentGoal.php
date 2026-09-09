<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\GoalStatus;
use App\Infrastructure\Repository\StudentGoalRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentGoalRepository::class)]
#[ORM\Table(name: 'student_goals')]
#[ORM\Index(name: 'idx_student_goals_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_student_goals_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_student_goals_status', columns: ['student_profile_id', 'status'])]
class StudentGoal
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

    #[ORM\ManyToOne(targetEntity: TrainerProfile::class)]
    #[ORM\JoinColumn(name: 'trainer_profile_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TrainerProfile $trainer = null;

    #[ORM\ManyToOne(targetEntity: MetricDefinition::class)]
    #[ORM\JoinColumn(name: 'metric_definition_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?MetricDefinition $metricDefinition = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $initialValue = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $targetValue = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $currentValue = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $targetDate = null;

    #[ORM\Column(type: 'string', length: 20, enumType: GoalStatus::class)]
    private GoalStatus $status;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $achievedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, GoalProgress>
     */
    #[ORM\OneToMany(targetEntity: GoalProgress::class, mappedBy: 'goal', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['recordedAt' => 'DESC', 'createdAt' => 'DESC'])]
    private Collection $progressUpdates;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        string $title,
        ?TrainerProfile $trainer = null,
        ?MetricDefinition $metricDefinition = null,
        ?string $description = null,
        ?float $initialValue = null,
        ?float $targetValue = null,
        ?float $currentValue = null,
        ?string $unit = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $targetDate = null,
        GoalStatus $status = GoalStatus::IN_PROGRESS,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->title = trim($title);
        $this->trainer = $trainer;
        $this->metricDefinition = $metricDefinition;
        $this->description = $description;
        $this->initialValue = $initialValue;
        $this->targetValue = $targetValue;
        $this->currentValue = $currentValue ?? $initialValue;
        $this->unit = $unit ?? $metricDefinition?->getUnit();
        $this->startDate = $startDate ?? new DateTimeImmutable();
        $this->targetDate = $targetDate;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->progressUpdates = new ArrayCollection();
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

    public function getTrainer(): ?TrainerProfile
    {
        return $this->trainer;
    }

    public function setTrainer(?TrainerProfile $trainer): self
    {
        $this->trainer = $trainer;
        $this->touch();
        return $this;
    }

    public function getMetricDefinition(): ?MetricDefinition
    {
        return $this->metricDefinition;
    }

    public function setMetricDefinition(?MetricDefinition $metricDefinition): self
    {
        $this->metricDefinition = $metricDefinition;
        $this->touch();
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function getInitialValue(): ?float
    {
        return $this->initialValue;
    }

    public function setInitialValue(?float $initialValue): self
    {
        $this->initialValue = $initialValue;
        $this->touch();
        return $this;
    }

    public function getTargetValue(): ?float
    {
        return $this->targetValue;
    }

    public function setTargetValue(?float $targetValue): self
    {
        $this->targetValue = $targetValue;
        $this->touch();
        return $this;
    }

    public function getCurrentValue(): ?float
    {
        return $this->currentValue;
    }

    public function setCurrentValue(?float $currentValue): self
    {
        $this->currentValue = $currentValue;
        $this->touch();
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        $this->touch();
        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        $this->touch();
        return $this;
    }

    public function getTargetDate(): ?DateTimeImmutable
    {
        return $this->targetDate;
    }

    public function setTargetDate(?DateTimeImmutable $targetDate): self
    {
        $this->targetDate = $targetDate;
        $this->touch();
        return $this;
    }

    public function getStatus(): GoalStatus
    {
        return $this->status;
    }

    public function setStatus(GoalStatus $status): self
    {
        $this->status = $status;
        if ($status === GoalStatus::ACHIEVED && $this->achievedAt === null) {
            $this->achievedAt = new DateTimeImmutable();
        }
        $this->touch();
        return $this;
    }

    public function getAchievedAt(): ?DateTimeImmutable
    {
        return $this->achievedAt;
    }

    public function setAchievedAt(?DateTimeImmutable $achievedAt): self
    {
        $this->achievedAt = $achievedAt;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, GoalProgress>
     */
    public function getProgressUpdates(): Collection
    {
        return $this->progressUpdates;
    }

    public function addProgressUpdate(GoalProgress $progress): self
    {
        if (!$this->progressUpdates->contains($progress)) {
            $this->progressUpdates->add($progress);
        }
        return $this;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
