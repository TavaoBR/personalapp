<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\UserStatus;
use App\Infrastructure\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 20, enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(type: 'boolean')]
    private bool $isPlatformAdmin = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, TenantMembership>
     */
    #[ORM\OneToMany(targetEntity: TenantMembership::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    public function __construct(
        string $email,
        string $passwordHash,
        string $name,
        ?string $id = null,
        ?string $phone = null,
        UserStatus $status = UserStatus::ACTIVE,
        bool $isPlatformAdmin = false
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->email = strtolower(trim($email));
        $this->passwordHash = $passwordHash;
        $this->name = trim($name);
        $this->phone = $phone !== null ? trim($phone) : null;
        $this->status = $status;
        $this->isPlatformAdmin = $isPlatformAdmin;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        $this->touch();
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        $this->touch();
        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone !== null ? trim($phone) : null;
        $this->touch();
        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isPlatformAdmin;
    }

    public function setIsPlatformAdmin(bool $isPlatformAdmin): self
    {
        $this->isPlatformAdmin = $isPlatformAdmin;
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
