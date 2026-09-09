<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\WorkoutTemplateVersionRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutTemplateVersionRepository::class)]
#[ORM\Table(name: 'workout_template_versions')]
#[ORM\Index(name: 'idx_template_version_lookup', columns: ['workout_template_id', 'version_number'])]
#[ORM\Index(name: 'idx_template_version_status', columns: ['workout_template_id', 'status'])]
class WorkoutTemplateVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutTemplate::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'workout_template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private WorkoutTemplate $workoutTemplate;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'DRAFT', 'PUBLISHED', 'ARCHIVED'

    #[ORM\Column(type: 'string', length: 80, nullable: true)]
    private ?string $objective = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $level = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @var Collection<int, WorkoutTemplateDay>
     */
    #[ORM\OneToMany(targetEntity: WorkoutTemplateDay::class, mappedBy: 'workoutTemplateVersion', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $days;

    public function __construct(
        WorkoutTemplate $workoutTemplate,
        int $versionNumber,
        string $status = 'PUBLISHED',
        ?string $objective = null,
        ?string $level = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->workoutTemplate = $workoutTemplate;
        $this->versionNumber = $versionNumber;
        $this->status = $status;
        $this->objective = $objective;
        $this->level = $level;
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
        if ($status === 'PUBLISHED') {
            $this->publishedAt = new DateTimeImmutable();
        }
        $this->days = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkoutTemplate(): WorkoutTemplate
    {
        return $this->workoutTemplate;
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
        if ($status === 'PUBLISHED' && $this->publishedAt === null) {
            $this->publishedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): self
    {
        $this->objective = $objective;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @return Collection<int, WorkoutTemplateDay>
     */
    public function getDays(): Collection
    {
        return $this->days;
    }

    public function addDay(WorkoutTemplateDay $day): self
    {
        if (!$this->days->contains($day)) {
            $this->days->add($day);
        }
        return $this;
    }
}
