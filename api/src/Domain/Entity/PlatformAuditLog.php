<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\PlatformAuditLogRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlatformAuditLogRepository::class)]
#[ORM\Table(name: 'platform_audit_logs')]
#[ORM\Index(name: 'idx_audit_admin', columns: ['admin_user_id'])]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
#[ORM\Index(name: 'idx_audit_created', columns: ['created_at'])]
class PlatformAuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'admin_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $adminUser;

    #[ORM\Column(type: 'string', length: 100)]
    private string $action;

    #[ORM\Column(type: 'string', length: 100)]
    private string $targetType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $targetId;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        User $adminUser,
        string $action,
        string $targetType,
        string $targetId,
        ?array $details = null,
        ?string $ipAddress = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->adminUser = $adminUser;
        $this->action = strtoupper(trim($action));
        $this->targetType = trim($targetType);
        $this->targetId = trim($targetId);
        $this->details = $details;
        $this->ipAddress = $ipAddress !== null ? trim($ipAddress) : null;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAdminUser(): User
    {
        return $this->adminUser;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
