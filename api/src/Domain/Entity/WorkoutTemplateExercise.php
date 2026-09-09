<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_template_exercises')]
#[ORM\Index(name: 'idx_template_exercise_day', columns: ['workout_template_day_id'])]
#[ORM\Index(name: 'idx_template_exercise_version', columns: ['exercise_version_id'])]
class WorkoutTemplateExercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutTemplateDay::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(name: 'workout_template_day_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutTemplateDay $workoutTemplateDay;

    #[ORM\ManyToOne(targetEntity: ExerciseVersion::class)]
    #[ORM\JoinColumn(name: 'exercise_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ExerciseVersion $exerciseVersion;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex;

    #[ORM\Column(type: 'integer')]
    private int $sets;

    #[ORM\Column(type: 'string', length: 50)]
    private string $reps;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $weight = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $restSeconds = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $rpe = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $technique = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(
        WorkoutTemplateDay $workoutTemplateDay,
        ExerciseVersion $exerciseVersion,
        int $orderIndex,
        int $sets,
        string $reps,
        ?string $weight = null,
        ?int $restSeconds = null,
        ?string $rpe = null,
        ?string $technique = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->workoutTemplateDay = $workoutTemplateDay;
        $this->exerciseVersion = $exerciseVersion;
        $this->orderIndex = $orderIndex;
        $this->sets = $sets;
        $this->reps = trim($reps);
        $this->weight = $weight !== null ? trim($weight) : null;
        $this->restSeconds = $restSeconds;
        $this->rpe = $rpe !== null ? trim($rpe) : null;
        $this->technique = $technique !== null ? trim($technique) : null;
        $this->notes = $notes;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutTemplateDay(): WorkoutTemplateDay
    {
        return $this->workoutTemplateDay;
    }

    public function getExerciseVersion(): ExerciseVersion
    {
        return $this->exerciseVersion;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function getSets(): int
    {
        return $this->sets;
    }

    public function setSets(int $sets): self
    {
        $this->sets = $sets;
        return $this;
    }

    public function getReps(): string
    {
        return $this->reps;
    }

    public function setReps(string $reps): self
    {
        $this->reps = trim($reps);
        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): self
    {
        $this->weight = $weight !== null ? trim($weight) : null;
        return $this;
    }

    public function getRestSeconds(): ?int
    {
        return $this->restSeconds;
    }

    public function setRestSeconds(?int $restSeconds): self
    {
        $this->restSeconds = $restSeconds;
        return $this;
    }

    public function getRpe(): ?string
    {
        return $this->rpe;
    }

    public function setRpe(?string $rpe): self
    {
        $this->rpe = $rpe !== null ? trim($rpe) : null;
        return $this;
    }

    public function getTechnique(): ?string
    {
        return $this->technique;
    }

    public function setTechnique(?string $technique): self
    {
        $this->technique = $technique !== null ? trim($technique) : null;
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
