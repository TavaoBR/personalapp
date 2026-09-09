<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\MetricDefinitionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MetricDefinitionRepository::class)]
#[ORM\Table(name: 'metric_definitions')]
#[ORM\Index(name: 'idx_metric_def_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_metric_def_code', columns: ['code'])]
class MetricDefinition
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string', length: 20)]
    private string $unit;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $code,
        string $unit,
        string $category = 'CUSTOM',
        ?Tenant $tenant = null,
        string $status = 'ACTIVE',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->name = trim($name);
        $this->code = strtoupper(trim($code));
        $this->unit = trim($unit);
        $this->category = strtoupper(trim($category));
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenant(): ?Tenant
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = trim($unit);
        $this->touch();
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = strtoupper(trim($category));
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
