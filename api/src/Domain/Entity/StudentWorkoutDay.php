<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'student_workout_days')]
#[ORM\Index(name: 'idx_student_day_version', columns: ['student_workout_plan_version_id'])]
class StudentWorkoutDay
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentWorkoutPlanVersion::class, inversedBy: 'days')]
    #[ORM\JoinColumn(name: 'student_workout_plan_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentWorkoutPlanVersion $studentWorkoutPlanVersion;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex;

    /**
     * @var Collection<int, StudentWorkoutExercise>
     */
    #[ORM\OneToMany(targetEntity: StudentWorkoutExercise::class, mappedBy: 'studentWorkoutDay', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $exercises;

    public function __construct(
        StudentWorkoutPlanVersion $studentWorkoutPlanVersion,
        string $name,
        int $orderIndex = 0,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->studentWorkoutPlanVersion = $studentWorkoutPlanVersion;
        $this->name = trim($name);
        $this->orderIndex = $orderIndex;
        $this->exercises = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStudentWorkoutPlanVersion(): StudentWorkoutPlanVersion
    {
        return $this->studentWorkoutPlanVersion;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
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

    /**
     * @return Collection<int, StudentWorkoutExercise>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(StudentWorkoutExercise $exercise): self
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }
        return $this;
    }
}
