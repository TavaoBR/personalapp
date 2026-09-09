<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentFollowUpPlanRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentFollowUpPlanRepository::class)]
#[ORM\Table(name: 'student_follow_up_plans')]
#[ORM\Index(name: 'idx_student_follow_up_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_student_follow_up_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_student_follow_up_status', columns: ['student_profile_id', 'status'])]
class StudentFollowUpPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: StudentProfile::class)]
    #[ORM\JoinColumn(name: 'student_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentProfile $student;

    #[ORM\ManyToOne(targetEntity: TrainerProfile::class)]
    #[ORM\JoinColumn(name: 'trainer_profile_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?TrainerProfile $trainer = null;

    #[ORM\ManyToOne(targetEntity: FollowUpPlanVersion::class)]
    #[ORM\JoinColumn(name: 'follow_up_plan_version_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FollowUpPlanVersion $templateVersion = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE'; // ACTIVE, COMPLETED, CANCELLED

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, StudentFollowUpTask>
     */
    #[ORM\OneToMany(targetEntity: StudentFollowUpTask::class, mappedBy: 'plan', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dueDate' => 'ASC', 'orderIndex' => 'ASC'])]
    private Collection $tasks;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        string $title,
        ?TrainerProfile $trainer = null,
        ?FollowUpPlanVersion $templateVersion = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        string $status = 'ACTIVE',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->title = trim($title);
        $this->trainer = $trainer;
        $this->templateVersion = $templateVersion;
        $this->startDate = $startDate ?? new DateTimeImmutable();
        $this->endDate = $endDate;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getStudent(): StudentProfile
    {
        return $this->student;
    }

    public function getTrainer(): ?TrainerProfile
    {
        return $this->trainer;
    }

    public function setTrainer(?TrainerProfile $trainer): self
    {
        $this->trainer = $trainer;
        $this->touch();
        return $this;
    }

    public function getTemplateVersion(): ?FollowUpPlanVersion
    {
        return $this->templateVersion;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        $this->touch();
        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        $this->touch();
        return $this;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
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

    /**
     * @return Collection<int, StudentFollowUpTask>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(StudentFollowUpTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
        }
        return $this;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
