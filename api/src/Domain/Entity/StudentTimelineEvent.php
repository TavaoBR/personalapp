<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Repository\StudentTimelineEventRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StudentTimelineEventRepository::class)]
#[ORM\Table(name: 'student_timeline_events')]
#[ORM\Index(name: 'idx_timeline_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_timeline_student', columns: ['student_profile_id'])]
#[ORM\Index(name: 'idx_timeline_student_occurred', columns: ['student_profile_id', 'occurred_at'])]
#[ORM\Index(name: 'idx_timeline_event_type', columns: ['student_profile_id', 'event_type'])]
class StudentTimelineEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: StudentProfile::class)]
    #[ORM\JoinColumn(name: 'student_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StudentProfile $student;

    #[ORM\Column(type: 'string', length: 50)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 150)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $entityId = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        Tenant $tenant,
        StudentProfile $student,
        string $eventType,
        string $title,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?DateTimeImmutable $occurredAt = null,
        ?string $id = null
    ) {
        $this->id = $id ?? Uuid::v7()->toRfc4122();
        $this->tenant = $tenant;
        $this->student = $student;
        $this->eventType = strtoupper(trim($eventType));
        $this->title = trim($title);
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getStudent(): StudentProfile
    {
        return $this->student;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
