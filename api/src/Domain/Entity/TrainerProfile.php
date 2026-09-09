<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\TrainerProfileRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TrainerProfileRepository::class)]
#[ORM\Table(name: 'trainer_profiles')]
#[ORM\UniqueConstraint(name: 'uniq_trainer_user_tenant', columns: ['user_id', 'tenant_id'])]
class TrainerProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $cref = null;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: 'json')]
    private array $specialties = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @param array<string> $specialties
     */
    public function __construct(
        User $user,
        Tenant $tenant,
        ?string $id = null,
        ?string $cref = null,
        array $specialties = [],
        ?string $bio = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->user = $user;
        $this->tenant = $tenant;
        $this->cref = $cref !== null ? trim($cref) : null;
        $this->specialties = $specialties;
        $this->bio = $bio !== null ? trim($bio) : null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getCref(): ?string
    {
        return $this->cref;
    }

    public function setCref(?string $cref): self
    {
        $this->cref = $cref !== null ? trim($cref) : null;
        $this->touch();
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getSpecialties(): array
    {
        return $this->specialties;
    }

    /**
     * @param array<string> $specialties
     */
    public function setSpecialties(array $specialties): self
    {
        $this->specialties = $specialties;
        $this->touch();
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio !== null ? trim($bio) : null;
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
