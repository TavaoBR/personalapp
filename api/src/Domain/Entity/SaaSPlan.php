<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\SaaSPlanRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SaaSPlanRepository::class)]
#[ORM\Table(name: 'saas_plans')]
#[ORM\Index(name: 'idx_saas_plan_code', columns: ['code'])]
#[ORM\Index(name: 'idx_saas_plan_status', columns: ['status'])]
class SaaSPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'float')]
    private float $priceMonthly;

    #[ORM\Column(type: 'float')]
    private float $priceYearly;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxStudents = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxTrainers = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, PlanFeature>
     */
    #[ORM\OneToMany(targetEntity: PlanFeature::class, mappedBy: 'plan', cascade: ['persist', 'remove'])]
    private Collection $features;

    public function __construct(
        string $code,
        string $name,
        float $priceMonthly,
        float $priceYearly,
        ?int $maxStudents = null,
        ?int $maxTrainers = null,
        ?string $description = null,
        string $status = 'ACTIVE',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->code = strtoupper(trim($code));
        $this->name = trim($name);
        $this->priceMonthly = max(0.0, $priceMonthly);
        $this->priceYearly = max(0.0, $priceYearly);
        $this->maxStudents = $maxStudents !== null ? max(1, $maxStudents) : null;
        $this->maxTrainers = $maxTrainers !== null ? max(1, $maxTrainers) : null;
        $this->description = $description;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->features = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
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

    public function getPriceMonthly(): float
    {
        return $this->priceMonthly;
    }

    public function setPriceMonthly(float $priceMonthly): self
    {
        $this->priceMonthly = max(0.0, $priceMonthly);
        $this->touch();
        return $this;
    }

    public function getPriceYearly(): float
    {
        return $this->priceYearly;
    }

    public function setPriceYearly(float $priceYearly): self
    {
        $this->priceYearly = max(0.0, $priceYearly);
        $this->touch();
        return $this;
    }

    public function getMaxStudents(): ?int
    {
        return $this->maxStudents;
    }

    public function setMaxStudents(?int $maxStudents): self
    {
        $this->maxStudents = $maxStudents !== null ? max(1, $maxStudents) : null;
        $this->touch();
        return $this;
    }

    public function getMaxTrainers(): ?int
    {
        return $this->maxTrainers;
    }

    public function setMaxTrainers(?int $maxTrainers): self
    {
        $this->maxTrainers = $maxTrainers !== null ? max(1, $maxTrainers) : null;
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
     * @return Collection<int, PlanFeature>
     */
    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(PlanFeature $feature): self
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }
        return $this;
    }

    public function hasFeature(string $featureCode): bool
    {
        $code = strtoupper(trim($featureCode));
        foreach ($this->features as $pf) {
            if ($pf->getFeature()->getCode() === $code) {
                return true;
            }
        }
        return false;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
