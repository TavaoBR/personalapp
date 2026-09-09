<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_exercise_executions')]
#[ORM\Index(name: 'idx_exercise_exec_session', columns: ['workout_session_id'])]
#[ORM\Index(name: 'idx_exercise_exec_version', columns: ['exercise_version_id'])]
class WorkoutExerciseExecution
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'exerciseExecutions')]
    #[ORM\JoinColumn(name: 'workout_session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutSession $workoutSession;

    #[ORM\ManyToOne(targetEntity: StudentWorkoutExercise::class)]
    #[ORM\JoinColumn(name: 'student_workout_exercise_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentWorkoutExercise $studentWorkoutExercise = null;

    #[ORM\ManyToOne(targetEntity: ExerciseVersion::class)]
    #[ORM\JoinColumn(name: 'exercise_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ExerciseVersion $exerciseVersion;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, WorkoutSetExecution>
     */
    #[ORM\OneToMany(targetEntity: WorkoutSetExecution::class, mappedBy: 'exerciseExecution', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['setNumber' => 'ASC'])]
    private Collection $setExecutions;

    public function __construct(
        WorkoutSession $workoutSession,
        ExerciseVersion $exerciseVersion,
        int $orderIndex = 0,
        ?StudentWorkoutExercise $studentWorkoutExercise = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->workoutSession = $workoutSession;
        $this->exerciseVersion = $exerciseVersion;
        $this->orderIndex = $orderIndex;
        $this->studentWorkoutExercise = $studentWorkoutExercise;
        $this->notes = $notes;
        $this->setExecutions = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutSession(): WorkoutSession
    {
        return $this->workoutSession;
    }

    public function getStudentWorkoutExercise(): ?StudentWorkoutExercise
    {
        return $this->studentWorkoutExercise;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Collection<int, WorkoutSetExecution>
     */
    public function getSetExecutions(): Collection
    {
        return $this->setExecutions;
    }

    public function addSetExecution(WorkoutSetExecution $setExecution): self
    {
        if (!$this->setExecutions->contains($setExecution)) {
            $this->setExecutions->add($setExecution);
        }
        return $this;
    }
}
