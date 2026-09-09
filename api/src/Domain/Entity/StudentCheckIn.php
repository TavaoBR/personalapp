<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentCheckInRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentCheckInRepository::class)]
#[ORM\Table(name: 'student_check_ins')]
#[ORM\Index(name: 'idx_check_in_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_check_in_student_date', columns: ['student_profile_id', 'check_in_date'])]
class StudentCheckIn
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

    #[ORM\ManyToOne(targetEntity: StudentFollowUpTask::class)]
    #[ORM\JoinColumn(name: 'student_follow_up_task_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentFollowUpTask $task = null;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $checkInDate;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $energyLevel = null; // 1 to 5

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $sleepQuality = null; // 1 to 5

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $sorenessLevel = null; // 1 to 5

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $dietAdherence = null; // 1 to 5

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $trainerFeedback = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'SUBMITTED'; // SUBMITTED, REVIEWED

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        ?StudentFollowUpTask $task = null,
        ?DateTimeImmutable $checkInDate = null,
        ?int $energyLevel = null,
        ?int $sleepQuality = null,
        ?int $sorenessLevel = null,
        ?int $dietAdherence = null,
        ?string $notes = null,
        string $status = 'SUBMITTED',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->task = $task;
        $this->checkInDate = $checkInDate ?? new DateTimeImmutable();
        $this->energyLevel = $energyLevel;
        $this->sleepQuality = $sleepQuality;
        $this->sorenessLevel = $sorenessLevel;
        $this->dietAdherence = $dietAdherence;
        $this->notes = $notes;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
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

    public function getTask(): ?StudentFollowUpTask
    {
        return $this->task;
    }

    public function getCheckInDate(): DateTimeImmutable
    {
        return $this->checkInDate;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function getSleepQuality(): ?int
    {
        return $this->sleepQuality;
    }

    public function getSorenessLevel(): ?int
    {
        return $this->sorenessLevel;
    }

    public function getDietAdherence(): ?int
    {
        return $this->dietAdherence;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getTrainerFeedback(): ?string
    {
        return $this->trainerFeedback;
    }

    public function setTrainerFeedback(?string $trainerFeedback): self
    {
        $this->trainerFeedback = $trainerFeedback;
        if ($trainerFeedback !== null) {
            $this->status = 'REVIEWED';
            $this->reviewedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getReviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
