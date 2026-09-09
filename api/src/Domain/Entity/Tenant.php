<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\TenantType;
use App\Infrastructure\Repository\TenantRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, enumType: TenantType::class)]
    private TenantType $type;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $document = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, TenantMembership>
     */
    #[ORM\OneToMany(targetEntity: TenantMembership::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    public function __construct(
        string $name,
        TenantType $type,
        ?string $id = null,
        ?string $document = null,
        string $status = 'ACTIVE'
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->name = trim($name);
        $this->type = $type;
        $this->document = $document !== null ? trim($document) : null;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getType(): TenantType
    {
        return $this->type;
    }

    public function setType(TenantType $type): self
    {
        $this->type = $type;
        $this->touch();
        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): self
    {
        $this->document = $document !== null ? trim($document) : null;
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
     * @return Collection<int, TenantMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
