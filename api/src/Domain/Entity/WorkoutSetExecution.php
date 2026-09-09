<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_set_executions')]
#[ORM\Index(name: 'idx_set_exec_exercise', columns: ['exercise_execution_id'])]
class WorkoutSetExecution
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutExerciseExecution::class, inversedBy: 'setExecutions')]
    #[ORM\JoinColumn(name: 'exercise_execution_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutExerciseExecution $exerciseExecution;

    #[ORM\Column(type: 'integer')]
    private int $setNumber;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $repsPrescribed = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $repsPerformed = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $weightPrescribed = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $weightPerformed = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $restSeconds = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rpe = null;

    #[ORM\Column(type: 'boolean')]
    private bool $completed = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct(
        WorkoutExerciseExecution $exerciseExecution,
        int $setNumber,
        ?string $repsPrescribed = null,
        ?string $weightPrescribed = null,
        ?int $restSeconds = null,
        ?int $repsPerformed = null,
        ?float $weightPerformed = null,
        ?float $rpe = null,
        bool $completed = false,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->exerciseExecution = $exerciseExecution;
        $this->setNumber = $setNumber;
        $this->repsPrescribed = $repsPrescribed;
        $this->weightPrescribed = $weightPrescribed;
        $this->restSeconds = $restSeconds;
        $this->repsPerformed = $repsPerformed;
        $this->weightPerformed = $weightPerformed;
        $this->rpe = $rpe;
        $this->completed = $completed;
        $this->notes = $notes;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExerciseExecution(): WorkoutExerciseExecution
    {
        return $this->exerciseExecution;
    }

    public function getSetNumber(): int
    {
        return $this->setNumber;
    }

    public function getRepsPrescribed(): ?string
    {
        return $this->repsPrescribed;
    }

    public function setRepsPrescribed(?string $repsPrescribed): self
    {
        $this->repsPrescribed = $repsPrescribed;
        return $this;
    }

    public function getRepsPerformed(): ?int
    {
        return $this->repsPerformed;
    }

    public function setRepsPerformed(?int $repsPerformed): self
    {
        $this->repsPerformed = $repsPerformed;
        return $this;
    }

    public function getWeightPrescribed(): ?string
    {
        return $this->weightPrescribed;
    }

    public function setWeightPrescribed(?string $weightPrescribed): self
    {
        $this->weightPrescribed = $weightPrescribed;
        return $this;
    }

    public function getWeightPerformed(): ?float
    {
        return $this->weightPerformed;
    }

    public function setWeightPerformed(?float $weightPerformed): self
    {
        $this->weightPerformed = $weightPerformed;
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

    public function getRpe(): ?float
    {
        return $this->rpe;
    }

    public function setRpe(?float $rpe): self
    {
        $this->rpe = $rpe;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;
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
