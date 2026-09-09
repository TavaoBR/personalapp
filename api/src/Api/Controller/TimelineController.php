<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\TenantContext;
use App\Application\Service\TimelineService;
use App\Domain\Entity\StudentTimelineEvent;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Infrastructure\Repository\StudentProfileRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class TimelineController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly TimelineService $timelineService,
        private readonly StudentProfileRepository $studentRepository
    ) {
    }

    #[Route('/api/students/{id}/timeline', name: 'api_student_timeline_list', methods: ['GET'])]
    public function listTimeline(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::VIEW_TIMELINE);

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $eventType = $request->query->get('event_type');
        $startDate = $request->query->has('start_date') ? new DateTimeImmutable((string) $request->query->get('start_date')) : null;
        $endDate = $request->query->has('end_date') ? new DateTimeImmutable((string) $request->query->get('end_date')) : null;
        $limit = $request->query->getInt('limit', 50);
        $offset = $request->query->getInt('offset', 0);

        $result = $this->timelineService->getStudentTimeline(
            tenant: $tenant,
            student: $student,
            eventType: $eventType,
            startDate: $startDate,
            endDate: $endDate,
            limit: $limit,
            offset: $offset
        );

        $data = array_map(fn (StudentTimelineEvent $e) => $this->serializeEvent($e), $result['items']);

        return new JsonResponse([
            'events' => $data,
            'total' => $result['total'],
            'limit' => $result['limit'],
            'offset' => $result['offset'],
        ]);
    }

    #[Route('/api/students/{id}/timeline', name: 'api_student_timeline_create', methods: ['POST'])]
    public function createManualEvent(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new BadRequestHttpException('O título do evento é obrigatório.');
        }

        $eventType = strtoupper(trim((string) ($data['event_type'] ?? 'NOTE_ADDED')));
        $description = isset($data['description']) ? (string) $data['description'] : null;
        $entityType = isset($data['entity_type']) ? (string) $data['entity_type'] : null;
        $entityId = isset($data['entity_id']) ? (string) $data['entity_id'] : null;
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;
        $occurredAt = isset($data['occurred_at']) ? new DateTimeImmutable((string) $data['occurred_at']) : null;

        $event = $this->timelineService->recordEvent(
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

        return new JsonResponse($this->serializeEvent($event), Response::HTTP_CREATED);
    }

    #[Route('/api/me/timeline', name: 'api_me_timeline_list', methods: ['GET'])]
    public function meTimeline(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByUserAndTenant($user, $tenant);

        if ($student === null) {
            throw new NotFoundHttpException('Perfil de aluno não encontrado para este tenant.');
        }

        $eventType = $request->query->get('event_type');
        $startDate = $request->query->has('start_date') ? new DateTimeImmutable((string) $request->query->get('start_date')) : null;
        $endDate = $request->query->has('end_date') ? new DateTimeImmutable((string) $request->query->get('end_date')) : null;
        $limit = $request->query->getInt('limit', 50);
        $offset = $request->query->getInt('offset', 0);

        $result = $this->timelineService->getStudentTimeline(
            tenant: $tenant,
            student: $student,
            eventType: $eventType,
            startDate: $startDate,
            endDate: $endDate,
            limit: $limit,
            offset: $offset
        );

        $data = array_map(fn (StudentTimelineEvent $e) => $this->serializeEvent($e), $result['items']);

        return new JsonResponse([
            'events' => $data,
            'total' => $result['total'],
            'limit' => $result['limit'],
            'offset' => $result['offset'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(StudentTimelineEvent $e): array
    {
        return [
            'id' => $e->getId(),
            'student_id' => $e->getStudent()->getId(),
            'event_type' => $e->getEventType(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'occurred_at' => $e->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'entity_type' => $e->getEntityType(),
            'entity_id' => $e->getEntityId(),
            'metadata' => $e->getMetadata(),
            'created_at' => $e->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
