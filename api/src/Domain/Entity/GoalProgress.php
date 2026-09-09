<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\GoalProgressRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GoalProgressRepository::class)]
#[ORM\Table(name: 'goal_progress')]
#[ORM\Index(name: 'idx_goal_progress_goal', columns: ['student_goal_id'])]
#[ORM\Index(name: 'idx_goal_progress_date', columns: ['student_goal_id', 'recorded_at'])]
class GoalProgress
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentGoal::class, inversedBy: 'progressUpdates')]
    #[ORM\JoinColumn(name: 'student_goal_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentGoal $goal;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $recordedAt;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $value = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $previousValue = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $source; // MANUAL, METRIC_RECORD, ASSESSMENT

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        StudentGoal $goal,
        ?float $value = null,
        ?float $previousValue = null,
        string $source = 'MANUAL',
        ?DateTimeImmutable $recordedAt = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->goal = $goal;
        $this->value = $value;
        $this->previousValue = $previousValue;
        $this->source = $source;
        $this->recordedAt = $recordedAt ?? new DateTimeImmutable();
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGoal(): StudentGoal
    {
        return $this->goal;
    }

    public function getRecordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getPreviousValue(): ?float
    {
        return $this->previousValue;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
