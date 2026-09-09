<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\MembershipStatus;
use App\Domain\Enum\TenantRole;
use App\Infrastructure\Repository\TenantMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TenantMembershipRepository::class)]
#[ORM\Table(name: 'tenant_memberships')]
#[ORM\UniqueConstraint(name: 'uniq_membership_user_tenant', columns: ['user_id', 'tenant_id'])]
class TenantMembership
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Tenant::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(type: 'string', length: 30, enumType: TenantRole::class)]
    private TenantRole $role;

    #[ORM\Column(type: 'string', length: 20, enumType: MembershipStatus::class)]
    private MembershipStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        Tenant $tenant,
        TenantRole $role,
        ?string $id = null,
        MembershipStatus $status = MembershipStatus::ACTIVE
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->user = $user;
        $this->tenant = $tenant;
        $this->role = $role;
        $this->status = $status;
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

    public function getRole(): TenantRole
    {
        return $this->role;
    }

    public function setRole(TenantRole $role): self
    {
        $this->role = $role;
        $this->touch();
        return $this;
    }

    public function getStatus(): MembershipStatus
    {
        return $this->status;
    }

    public function setStatus(MembershipStatus $status): self
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
