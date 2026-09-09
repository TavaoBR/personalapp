<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\MuscleGroup;
use App\Infrastructure\Repository\ExerciseVersionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExerciseVersionRepository::class)]
#[ORM\Table(name: 'exercise_versions')]
#[ORM\Index(name: 'idx_exercise_version_tenant_ex', columns: ['tenant_exercise_id', 'version_number'])]
#[ORM\Index(name: 'idx_exercise_version_global_ex', columns: ['global_exercise_id', 'version_number'])]
class ExerciseVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: GlobalExercise::class)]
    #[ORM\JoinColumn(name: 'global_exercise_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GlobalExercise $globalExercise = null;

    #[ORM\ManyToOne(targetEntity: TenantExercise::class)]
    #[ORM\JoinColumn(name: 'tenant_exercise_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TenantExercise $tenantExercise = null;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

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

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $name,
        MuscleGroup $muscleGroup,
        int $versionNumber,
        ?TenantExercise $tenantExercise = null,
        ?GlobalExercise $globalExercise = null,
        ?string $equipment = null,
        ?string $description = null,
        ?string $videoUrl = null,
        ?string $id = null
    ) {
        if ($tenantExercise === null && $globalExercise === null) {
            throw new InvalidArgumentException('ExerciseVersion deve estar vinculada a um GlobalExercise ou TenantExercise.');
        }

        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->name = trim($name);
        $this->muscleGroup = $muscleGroup;
        $this->versionNumber = $versionNumber;
        $this->tenantExercise = $tenantExercise;
        $this->globalExercise = $globalExercise;
        $this->equipment = $equipment !== null ? trim($equipment) : null;
        $this->description = $description;
        $this->videoUrl = $videoUrl;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGlobalExercise(): ?GlobalExercise
    {
        return $this->globalExercise;
    }

    public function getTenantExercise(): ?TenantExercise
    {
        return $this->tenantExercise;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMuscleGroup(): MuscleGroup
    {
        return $this->muscleGroup;
    }

    public function getEquipment(): ?string
    {
        return $this->equipment;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
