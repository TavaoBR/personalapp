<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\PaymentStatus;
use App\Infrastructure\Repository\StudentPaymentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentPaymentRepository::class)]
#[ORM\Table(name: 'student_payments')]
#[ORM\Index(name: 'idx_payment_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_payment_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_payment_status', columns: ['tenant_id', 'status'])]
#[ORM\Index(name: 'idx_payment_due', columns: ['tenant_id', 'due_date'])]
class StudentPayment
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

    #[ORM\ManyToOne(targetEntity: StudentEnrollment::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'student_enrollment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?StudentEnrollment $enrollment = null;

    #[ORM\Column(type: 'float')]
    private float $amount;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $paymentMethod = null; // PIX, CREDIT_CARD, BOLETO, CASH, BANK_TRANSFER, OTHER

    #[ORM\Column(type: 'string', length: 20, enumType: PaymentStatus::class)]
    private PaymentStatus $status = PaymentStatus::PENDING;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $referencePeriod = null; // e.g. "2026-03"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        float $amount,
        DateTimeImmutable $dueDate,
        ?StudentEnrollment $enrollment = null,
        PaymentStatus $status = PaymentStatus::PENDING,
        ?string $referencePeriod = null,
        ?string $notes = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->amount = max(0.0, $amount);
        $this->dueDate = $dueDate;
        $this->enrollment = $enrollment;
        $this->status = $status;
        $this->referencePeriod = $referencePeriod;
        $this->notes = $notes;
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

    public function getStudent(): StudentProfile
    {
        return $this->student;
    }

    public function getEnrollment(): ?StudentEnrollment
    {
        return $this->enrollment;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = max(0.0, $amount);
        $this->touch();
        return $this;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(DateTimeImmutable $dueDate): self
    {
        $this->dueDate = $dueDate;
        $this->touch();
        return $this;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function markAsPaid(string $paymentMethod = 'PIX', ?DateTimeImmutable $paidAt = null): self
    {
        $this->status = PaymentStatus::PAID;
        $this->paymentMethod = strtoupper(trim($paymentMethod));
        $this->paidAt = $paidAt ?? new DateTimeImmutable();
        $this->touch();
        return $this;
    }

    public function getReferencePeriod(): ?string
    {
        return $this->referencePeriod;
    }

    public function setReferencePeriod(?string $referencePeriod): self
    {
        $this->referencePeriod = $referencePeriod;
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

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
