<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'plan_features')]
#[ORM\Index(name: 'idx_plan_feature_plan', columns: ['saas_plan_id'])]
#[ORM\Index(name: 'idx_plan_feature_feature', columns: ['feature_id'])]
class PlanFeature
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: SaaSPlan::class, inversedBy: 'features')]
    #[ORM\JoinColumn(name: 'saas_plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private SaaSPlan $plan;

    #[ORM\ManyToOne(targetEntity: Feature::class)]
    #[ORM\JoinColumn(name: 'feature_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Feature $feature;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $limitValue = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        SaaSPlan $plan,
        Feature $feature,
        ?int $limitValue = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->plan = $plan;
        $this->feature = $feature;
        $this->limitValue = $limitValue;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPlan(): SaaSPlan
    {
        return $this->plan;
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function getLimitValue(): ?int
    {
        return $this->limitValue;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
