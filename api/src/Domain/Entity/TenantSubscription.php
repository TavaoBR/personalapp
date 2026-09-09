<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\TenantSubscriptionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TenantSubscriptionRepository::class)]
#[ORM\Table(name: 'tenant_subscriptions')]
#[ORM\Index(name: 'idx_subscription_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_subscription_status', columns: ['tenant_id', 'status'])]
#[ORM\Index(name: 'idx_subscription_period_end', columns: ['current_period_end'])]
class TenantSubscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: SaaSPlan::class)]
    #[ORM\JoinColumn(name: 'saas_plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private SaaSPlan $plan;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = 'ACTIVE'; // TRIALING, ACTIVE, PAST_DUE, CANCELED, EXPIRED

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $currentPeriodStart;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $currentPeriodEnd;

    #[ORM\Column(type: 'boolean')]
    private bool $cancelAtPeriodEnd = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $canceledAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Tenant $tenant,
        SaaSPlan $plan,
        string $status = 'ACTIVE',
        ?DateTimeImmutable $startedAt = null,
        ?DateTimeImmutable $currentPeriodStart = null,
        ?DateTimeImmutable $currentPeriodEnd = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->plan = $plan;
        $this->status = $status;
        $this->startedAt = $startedAt ?? new DateTimeImmutable();
        $this->currentPeriodStart = $currentPeriodStart ?? $this->startedAt;
        $this->currentPeriodEnd = $currentPeriodEnd ?? $this->currentPeriodStart->modify('+30 days');
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

    public function getPlan(): SaaSPlan
    {
        return $this->plan;
    }

    public function setPlan(SaaSPlan $plan): self
    {
        $this->plan = $plan;
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

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCurrentPeriodStart(): DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function getCurrentPeriodEnd(): DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriod(DateTimeImmutable $start, DateTimeImmutable $end): self
    {
        $this->currentPeriodStart = $start;
        $this->currentPeriodEnd = $end;
        $this->touch();
        return $this;
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): self
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        $this->touch();
        return $this;
    }

    public function getCanceledAt(): ?DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function cancel(?DateTimeImmutable $canceledAt = null): self
    {
        $this->status = 'CANCELED';
        $this->canceledAt = $canceledAt ?? new DateTimeImmutable();
        $this->touch();
        return $this;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['ACTIVE', 'TRIALING'], true);
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
