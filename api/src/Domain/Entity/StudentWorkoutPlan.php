<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentWorkoutPlanRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentWorkoutPlanRepository::class)]
#[ORM\Table(name: 'student_workout_plans')]
#[ORM\Index(name: 'idx_student_plan_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_student_plan_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_student_plan_status', columns: ['student_profile_id', 'status'])]
class StudentWorkoutPlan
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

    #[ORM\ManyToOne(targetEntity: WorkoutTemplate::class)]
    #[ORM\JoinColumn(name: 'source_template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?WorkoutTemplate $sourceTemplate = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, StudentWorkoutPlanVersion>
     */
    #[ORM\OneToMany(targetEntity: StudentWorkoutPlanVersion::class, mappedBy: 'studentWorkoutPlan', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['versionNumber' => 'DESC'])]
    private Collection $versions;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        string $name,
        ?WorkoutTemplate $sourceTemplate = null,
        ?string $id = null,
        string $status = 'ACTIVE'
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->name = trim($name);
        $this->sourceTemplate = $sourceTemplate;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->versions = new ArrayCollection();
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

    public function getSourceTemplate(): ?WorkoutTemplate
    {
        return $this->sourceTemplate;
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
     * @return Collection<int, StudentWorkoutPlanVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(StudentWorkoutPlanVersion $version): self
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
        }
        return $this;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
