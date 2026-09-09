<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\EnrollmentStatus;
use App\Infrastructure\Repository\StudentEnrollmentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentEnrollmentRepository::class)]
#[ORM\Table(name: 'student_enrollments')]
#[ORM\Index(name: 'idx_enrollment_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_enrollment_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_enrollment_status', columns: ['student_profile_id', 'status'])]
class StudentEnrollment
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

    #[ORM\ManyToOne(targetEntity: CoachingPlan::class)]
    #[ORM\JoinColumn(name: 'coaching_plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private CoachingPlan $coachingPlan;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(type: 'smallint')]
    private int $billingDay = 10;

    #[ORM\Column(type: 'string', length: 20, enumType: EnrollmentStatus::class)]
    private EnrollmentStatus $status = EnrollmentStatus::ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, StudentPayment>
     */
    #[ORM\OneToMany(targetEntity: StudentPayment::class, mappedBy: 'enrollment', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dueDate' => 'ASC'])]
    private Collection $payments;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        CoachingPlan $coachingPlan,
        ?float $price = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        int $billingDay = 10,
        EnrollmentStatus $status = EnrollmentStatus::ACTIVE,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->coachingPlan = $coachingPlan;
        $this->price = $price ?? $coachingPlan->getPrice();
        $this->startDate = $startDate ?? new DateTimeImmutable();
        $this->endDate = $endDate;
        $this->billingDay = max(1, min(31, $billingDay));
        $this->status = $status;
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->payments = new ArrayCollection();
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

    public function getCoachingPlan(): CoachingPlan
    {
        return $this->coachingPlan;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = max(0.0, $price);
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

    public function getBillingDay(): int
    {
        return $this->billingDay;
    }

    public function setBillingDay(int $billingDay): self
    {
        $this->billingDay = max(1, min(31, $billingDay));
        $this->touch();
        return $this;
    }

    public function getStatus(): EnrollmentStatus
    {
        return $this->status;
    }

    public function setStatus(EnrollmentStatus $status): self
    {
        $this->status = $status;
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, StudentPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(StudentPayment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
        }
        return $this;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
