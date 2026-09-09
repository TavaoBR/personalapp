<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\AssignmentStatus;
use App\Infrastructure\Repository\TrainerStudentAssignmentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TrainerStudentAssignmentRepository::class)]
#[ORM\Table(name: 'trainer_student_assignments')]
#[ORM\Index(name: 'idx_assignment_tenant_trainer', columns: ['tenant_id', 'trainer_id'])]
#[ORM\Index(name: 'idx_assignment_tenant_student', columns: ['tenant_id', 'student_id'])]
class TrainerStudentAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: TrainerProfile::class)]
    #[ORM\JoinColumn(name: 'trainer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TrainerProfile $trainer;

    #[ORM\ManyToOne(targetEntity: StudentProfile::class)]
    #[ORM\JoinColumn(name: 'student_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentProfile $student;

    #[ORM\Column(type: 'boolean')]
    private bool $isPrimary;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: AssignmentStatus::class)]
    private AssignmentStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Tenant $tenant,
        TrainerProfile $trainer,
        StudentProfile $student,
        bool $isPrimary = true,
        ?string $id = null,
        ?DateTimeImmutable $startedAt = null,
        AssignmentStatus $status = AssignmentStatus::ACTIVE
    ) {
        // Validação estrita de isolamento de tenant no domínio
        if ($trainer->getTenant()->getId() !== $tenant->getId()) {
            throw new InvalidArgumentException('O treinador não pertence ao tenant especificado.');
        }

        if ($student->getTenant()->getId() !== $tenant->getId()) {
            throw new InvalidArgumentException('O aluno não pertence ao tenant especificado.');
        }

        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->trainer = $trainer;
        $this->student = $student;
        $this->isPrimary = $isPrimary;
        $this->startedAt = $startedAt ?? new DateTimeImmutable();
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getTrainer(): TrainerProfile
    {
        return $this->trainer;
    }

    public function getStudent(): StudentProfile
    {
        return $this->student;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        $this->touch();
        return $this;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function endAssignment(?DateTimeImmutable $endedAt = null): void
    {
        $this->endedAt = $endedAt ?? new DateTimeImmutable();
        $this->status = AssignmentStatus::ENDED;
        $this->touch();
    }

    public function getStatus(): AssignmentStatus
    {
        return $this->status;
    }

    public function setStatus(AssignmentStatus $status): self
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
