<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentAnamnesisRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentAnamnesisRepository::class)]
#[ORM\Table(name: 'student_anamneses')]
#[ORM\Index(name: 'idx_student_anamnesis_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_student_anamnesis_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_student_anamnesis_date', columns: ['student_profile_id', 'answered_at'])]
class StudentAnamnesis
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

    #[ORM\ManyToOne(targetEntity: AnamnesisTemplateVersion::class)]
    #[ORM\JoinColumn(name: 'template_version_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private AnamnesisTemplateVersion $templateVersion;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $answeredAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'SUBMITTED';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, StudentAnamnesisAnswer>
     */
    #[ORM\OneToMany(targetEntity: StudentAnamnesisAnswer::class, mappedBy: 'studentAnamnesis', cascade: ['persist', 'remove'])]
    private Collection $answers;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        AnamnesisTemplateVersion $templateVersion,
        ?DateTimeImmutable $answeredAt = null,
        ?string $notes = null,
        string $status = 'SUBMITTED',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->templateVersion = $templateVersion;
        $this->answeredAt = $answeredAt ?? new DateTimeImmutable();
        $this->notes = $notes;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->answers = new ArrayCollection();
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

    public function getTemplateVersion(): AnamnesisTemplateVersion
    {
        return $this->templateVersion;
    }

    public function getAnsweredAt(): DateTimeImmutable
    {
        return $this->answeredAt;
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

    /**
     * @return Collection<int, StudentAnamnesisAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(StudentAnamnesisAnswer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
        }
        return $this;
    }
}
