<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentFollowUpTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentFollowUpTaskRepository::class)]
#[ORM\Table(name: 'student_follow_up_tasks')]
#[ORM\Index(name: 'idx_follow_up_task_plan', columns: ['student_follow_up_plan_id'])]
#[ORM\Index(name: 'idx_follow_up_task_due', columns: ['student_follow_up_plan_id', 'due_date'])]
class StudentFollowUpTask
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentFollowUpPlan::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'student_follow_up_plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentFollowUpPlan $plan;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'string', length: 30)]
    private string $taskType; // ANAMNESIS, ASSESSMENT, WORKOUT_REVIEW, CHECK_IN, GOAL_REVIEW, CUSTOM

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING'; // PENDING, COMPLETED, SKIPPED, OVERDUE

    #[ORM\Column(type: 'integer')]
    private int $orderIndex = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        StudentFollowUpPlan $plan,
        string $title,
        string $taskType,
        DateTimeImmutable $dueDate,
        int $orderIndex = 0,
        string $status = 'PENDING',
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->plan = $plan;
        $this->title = trim($title);
        $this->taskType = strtoupper(trim($taskType));
        $this->dueDate = $dueDate;
        $this->orderIndex = $orderIndex;
        $this->status = $status;
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPlan(): StudentFollowUpPlan
    {
        return $this->plan;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        return $this;
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(DateTimeImmutable $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        if ($status === 'COMPLETED' && $this->completedAt === null) {
            $this->completedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
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
}
