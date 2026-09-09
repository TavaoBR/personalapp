<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\BillingPeriod;
use App\Infrastructure\Repository\CoachingPlanRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CoachingPlanRepository::class)]
#[ORM\Table(name: 'coaching_plans')]
#[ORM\Index(name: 'idx_coaching_plan_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_coaching_plan_status', columns: ['tenant_id', 'status'])]
class CoachingPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\Column(type: 'string', length: 30, enumType: BillingPeriod::class)]
    private BillingPeriod $billingPeriod;

    #[ORM\Column(type: 'integer')]
    private int $durationMonths;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Tenant $tenant,
        string $name,
        float $price,
        BillingPeriod $billingPeriod = BillingPeriod::MONTHLY,
        int $durationMonths = 1,
        ?string $description = null,
        string $status = 'ACTIVE',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->name = trim($name);
        $this->price = max(0.0, $price);
        $this->billingPeriod = $billingPeriod;
        $this->durationMonths = max(1, $durationMonths);
        $this->description = $description;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
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

    public function getBillingPeriod(): BillingPeriod
    {
        return $this->billingPeriod;
    }

    public function setBillingPeriod(BillingPeriod $billingPeriod): self
    {
        $this->billingPeriod = $billingPeriod;
        $this->touch();
        return $this;
    }

    public function getDurationMonths(): int
    {
        return $this->durationMonths;
    }

    public function setDurationMonths(int $durationMonths): self
    {
        $this->durationMonths = max(1, $durationMonths);
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

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
