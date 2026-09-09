<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\PhysicalAssessmentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PhysicalAssessmentRepository::class)]
#[ORM\Table(name: 'physical_assessments')]
#[ORM\Index(name: 'idx_assessment_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_assessment_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_assessment_date', columns: ['student_profile_id', 'date'])]
class PhysicalAssessment
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

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'COMPLETED';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, AssessmentMetric>
     */
    #[ORM\OneToMany(targetEntity: AssessmentMetric::class, mappedBy: 'physicalAssessment', cascade: ['persist', 'remove'])]
    private Collection $metrics;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        ?DateTimeImmutable $date = null,
        ?TrainerProfile $trainer = null,
        ?string $notes = null,
        string $status = 'COMPLETED',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->trainer = $trainer;
        $this->date = $date ?? new DateTimeImmutable('today');
        $this->notes = $notes;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->metrics = new ArrayCollection();
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

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): self
    {
        $this->date = $date;
        $this->touch();
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
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
     * @return Collection<int, AssessmentMetric>
     */
    public function getMetrics(): Collection
    {
        return $this->metrics;
    }

    public function addMetric(AssessmentMetric $metric): self
    {
        if (!$this->metrics->contains($metric)) {
            $this->metrics->add($metric);
        }
        return $this;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
