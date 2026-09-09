<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentWorkoutPlanVersionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentWorkoutPlanVersionRepository::class)]
#[ORM\Table(name: 'student_workout_plan_versions')]
#[ORM\Index(name: 'idx_plan_version_lookup', columns: ['student_workout_plan_id', 'version_number'])]
#[ORM\Index(name: 'idx_plan_version_status', columns: ['student_workout_plan_id', 'status'])]
class StudentWorkoutPlanVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentWorkoutPlan::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'student_workout_plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentWorkoutPlan $studentWorkoutPlan;

    #[ORM\ManyToOne(targetEntity: WorkoutTemplateVersion::class)]
    #[ORM\JoinColumn(name: 'source_template_version_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?WorkoutTemplateVersion $sourceTemplateVersion = null;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'ACTIVE', 'ARCHIVED'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $validUntil = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, StudentWorkoutDay>
     */
    #[ORM\OneToMany(targetEntity: StudentWorkoutDay::class, mappedBy: 'studentWorkoutPlanVersion', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $days;

    public function __construct(
        StudentWorkoutPlan $studentWorkoutPlan,
        int $versionNumber,
        string $status = 'ACTIVE',
        ?WorkoutTemplateVersion $sourceTemplateVersion = null,
        ?string $notes = null,
        ?DateTimeImmutable $validFrom = null,
        ?DateTimeImmutable $validUntil = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->studentWorkoutPlan = $studentWorkoutPlan;
        $this->versionNumber = $versionNumber;
        $this->status = $status;
        $this->sourceTemplateVersion = $sourceTemplateVersion;
        $this->notes = $notes;
        $this->validFrom = $validFrom ?? new DateTimeImmutable();
        $this->validUntil = $validUntil;
        $this->createdAt = new DateTimeImmutable();
        $this->days = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStudentWorkoutPlan(): StudentWorkoutPlan
    {
        return $this->studentWorkoutPlan;
    }

    public function getSourceTemplateVersion(): ?WorkoutTemplateVersion
    {
        return $this->sourceTemplateVersion;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getValidFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(?DateTimeImmutable $validFrom): self
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?DateTimeImmutable $validUntil): self
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, StudentWorkoutDay>
     */
    public function getDays(): Collection
    {
        return $this->days;
    }

    public function addDay(StudentWorkoutDay $day): self
    {
        if (!$this->days->contains($day)) {
            $this->days->add($day);
        }
        return $this;
    }
}
