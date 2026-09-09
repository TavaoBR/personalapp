<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\StudentTimelineEvent;
use App\Domain\Entity\Tenant;
use App\Infrastructure\Repository\StudentTimelineEventRepository;
use DateTimeImmutable;
use InvalidArgumentException;

class TimelineService
{
    public function __construct(
        private readonly StudentTimelineEventRepository $timelineRepository
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function recordEvent(
        Tenant $tenant,
        StudentProfile $student,
        string $eventType,
        string $title,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?DateTimeImmutable $occurredAt = null
    ): StudentTimelineEvent {
        if (trim($title) === '') {
            throw new InvalidArgumentException('O título do evento da timeline é obrigatório.');
        }

        $event = new StudentTimelineEvent(
            tenant: $tenant,
            student: $student,
            eventType: $eventType,
            title: $title,
            entityType: $entityType,
            entityId: $entityId,
            description: $description,
            metadata: $metadata,
            occurredAt: $occurredAt
        );

        $this->timelineRepository->save($event);

        return $event;
    }

    /**
     * @return array{items: array<StudentTimelineEvent>, total: int, limit: int, offset: int}
     */
    public function getStudentTimeline(
        Tenant $tenant,
        StudentProfile $student,
        ?string $eventType = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $events = $this->timelineRepository->findTimeline(
            student: $student,
            tenant: $tenant,
            eventType: $eventType,
            startDate: $startDate,
            endDate: $endDate,
            limit: $limit,
            offset: $offset
        );

        $total = $this->timelineRepository->countTimeline(
            student: $student,
            tenant: $tenant,
            eventType: $eventType,
            startDate: $startDate,
            endDate: $endDate
        );

        return [
            'items' => $events,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
