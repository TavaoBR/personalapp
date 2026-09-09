<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\WorkoutSessionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutSessionRepository::class)]
#[ORM\Table(name: 'workout_sessions')]
#[ORM\Index(name: 'idx_session_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_session_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_session_status', columns: ['tenant_id', 'status'])]
#[ORM\Index(name: 'idx_session_started_at', columns: ['student_profile_id', 'started_at'])]
class WorkoutSession
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

    #[ORM\ManyToOne(targetEntity: StudentWorkoutPlan::class)]
    #[ORM\JoinColumn(name: 'workout_plan_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentWorkoutPlan $workoutPlan = null;

    #[ORM\ManyToOne(targetEntity: StudentWorkoutPlanVersion::class)]
    #[ORM\JoinColumn(name: 'workout_plan_version_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentWorkoutPlanVersion $workoutPlanVersion = null;

    #[ORM\ManyToOne(targetEntity: StudentWorkoutDay::class)]
    #[ORM\JoinColumn(name: 'workout_day_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentWorkoutDay $workoutDay = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationSeconds = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'IN_PROGRESS', 'COMPLETED', 'ABANDONED'

    #[ORM\Column(type: 'float')]
    private float $completionPercentage = 0.0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedbackNotes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, WorkoutExerciseExecution>
     */
    #[ORM\OneToMany(targetEntity: WorkoutExerciseExecution::class, mappedBy: 'workoutSession', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $exerciseExecutions;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        ?StudentWorkoutPlan $workoutPlan = null,
        ?StudentWorkoutPlanVersion $workoutPlanVersion = null,
        ?StudentWorkoutDay $workoutDay = null,
        ?DateTimeImmutable $startedAt = null,
        string $status = 'IN_PROGRESS',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->workoutPlan = $workoutPlan;
        $this->workoutPlanVersion = $workoutPlanVersion;
        $this->workoutDay = $workoutDay;
        $this->startedAt = $startedAt ?? new DateTimeImmutable();
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->exerciseExecutions = new ArrayCollection();
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

    public function getWorkoutPlan(): ?StudentWorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function getWorkoutPlanVersion(): ?StudentWorkoutPlanVersion
    {
        return $this->workoutPlanVersion;
    }

    public function getWorkoutDay(): ?StudentWorkoutDay
    {
        return $this->workoutDay;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(?int $durationSeconds): self
    {
        $this->durationSeconds = $durationSeconds;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }

    public function setCompletionPercentage(float $completionPercentage): self
    {
        $this->completionPercentage = $completionPercentage;
        return $this;
    }

    public function getFeedbackNotes(): ?string
    {
        return $this->feedbackNotes;
    }

    public function setFeedbackNotes(?string $feedbackNotes): self
    {
        $this->feedbackNotes = $feedbackNotes;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, WorkoutExerciseExecution>
     */
    public function getExerciseExecutions(): Collection
    {
        return $this->exerciseExecutions;
    }

    public function addExerciseExecution(WorkoutExerciseExecution $execution): self
    {
        if (!$this->exerciseExecutions->contains($execution)) {
            $this->exerciseExecutions->add($execution);
        }
        return $this;
    }

    public function complete(?DateTimeImmutable $finishedAt = null, ?string $feedbackNotes = null): void
    {
        $finish = $finishedAt ?? new DateTimeImmutable();
        $this->finishedAt = $finish;
        $this->status = 'COMPLETED';
        $this->durationSeconds = max(0, $finish->getTimestamp() - $this->startedAt->getTimestamp());
        if ($feedbackNotes !== null) {
            $this->feedbackNotes = $feedbackNotes;
        }

        $this->recalculateCompletionPercentage();
    }

    public function recalculateCompletionPercentage(): void
    {
        $totalSets = 0;
        $completedSets = 0;

        foreach ($this->exerciseExecutions as $ex) {
            foreach ($ex->getSetExecutions() as $set) {
                $totalSets++;
                if ($set->isCompleted()) {
                    $completedSets++;
                }
            }
        }

        $this->completionPercentage = $totalSets > 0
            ? round(($completedSets / $totalSets) * 100, 1)
            : 100.0;
    }
}
