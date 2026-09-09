<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_template_days')]
#[ORM\Index(name: 'idx_template_day_version', columns: ['workout_template_version_id'])]
class WorkoutTemplateDay
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutTemplateVersion::class, inversedBy: 'days')]
    #[ORM\JoinColumn(name: 'workout_template_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutTemplateVersion $workoutTemplateVersion;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $orderIndex;

    /**
     * @var Collection<int, WorkoutTemplateExercise>
     */
    #[ORM\OneToMany(targetEntity: WorkoutTemplateExercise::class, mappedBy: 'workoutTemplateDay', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $exercises;

    public function __construct(
        WorkoutTemplateVersion $workoutTemplateVersion,
        string $name,
        int $orderIndex = 0,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->workoutTemplateVersion = $workoutTemplateVersion;
        $this->name = trim($name);
        $this->orderIndex = $orderIndex;
        $this->exercises = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutTemplateVersion(): WorkoutTemplateVersion
    {
        return $this->workoutTemplateVersion;
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
     * @return Collection<int, WorkoutTemplateExercise>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(WorkoutTemplateExercise $exercise): self
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }
        return $this;
    }
}
