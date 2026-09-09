<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\MuscleGroup;
use App\Infrastructure\Repository\GlobalExerciseRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GlobalExerciseRepository::class)]
#[ORM\Table(name: 'global_exercises')]
#[ORM\Index(name: 'idx_global_exercise_muscle', columns: ['muscle_group'])]
#[ORM\Index(name: 'idx_global_exercise_name', columns: ['name'])]
class GlobalExercise
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 30, enumType: MuscleGroup::class)]
    private MuscleGroup $muscleGroup;

    #[ORM\Column(type: 'string', length: 80, nullable: true)]
    private ?string $equipment = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        MuscleGroup $muscleGroup,
        ?string $equipment = null,
        ?string $description = null,
        ?string $videoUrl = null,
        ?string $id = null,
        string $status = 'ACTIVE'
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->name = trim($name);
        $this->muscleGroup = $muscleGroup;
        $this->equipment = $equipment !== null ? trim($equipment) : null;
        $this->description = $description;
        $this->videoUrl = $videoUrl;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        $this->touch();
        return $this;
    }

    public function getMuscleGroup(): MuscleGroup
    {
        return $this->muscleGroup;
    }

    public function setMuscleGroup(MuscleGroup $muscleGroup): self
    {
        $this->muscleGroup = $muscleGroup;
        $this->touch();
        return $this;
    }

    public function getEquipment(): ?string
    {
        return $this->equipment;
    }

    public function setEquipment(?string $equipment): self
    {
        $this->equipment = $equipment !== null ? trim($equipment) : null;
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

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;
        $this->touch();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
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

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
