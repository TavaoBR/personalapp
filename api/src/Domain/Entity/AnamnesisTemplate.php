<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\AnamnesisTemplateRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnamnesisTemplateRepository::class)]
#[ORM\Table(name: 'anamnesis_templates')]
#[ORM\Index(name: 'idx_anamnesis_template_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_anamnesis_template_status', columns: ['tenant_id', 'status'])]
class AnamnesisTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, AnamnesisTemplateVersion>
     */
    #[ORM\OneToMany(targetEntity: AnamnesisTemplateVersion::class, mappedBy: 'anamnesisTemplate', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['versionNumber' => 'DESC'])]
    private Collection $versions;

    public function __construct(
        Tenant $tenant,
        string $title,
        ?string $description = null,
        string $status = 'ACTIVE',
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->title = trim($title);
        $this->description = $description;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
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
     * @return Collection<int, AnamnesisTemplateVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(AnamnesisTemplateVersion $version): self
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
