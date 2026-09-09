<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Service\PlatformAdminService;
use App\Domain\Entity\PlatformAuditLog;
use App\Domain\Entity\SaaSPlan;
use App\Domain\Entity\TenantSubscription;
use App\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/admin', name: 'api_admin_')]
final class PlatformAdminController extends AbstractController
{
    public function __construct(
        private readonly PlatformAdminService $adminService
    ) {
    }

    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->adminService->getMetrics($user);
        return new JsonResponse($data);
    }

    #[Route('/tenants', name: 'tenants_list', methods: ['GET'])]
    public function listTenants(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $status = $request->query->getString('status') ?: null;
        $search = $request->query->getString('search') ?: null;
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        $data = $this->adminService->listTenants($user, $status, $search, $page, $limit);
        return new JsonResponse($data);
    }

    #[Route('/tenants/{id}', name: 'tenants_detail', methods: ['GET'])]
    public function tenantDetail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->adminService->getTenantDetail($user, $id);
        return new JsonResponse($data);
    }

    #[Route('/tenants/{id}/status', name: 'tenants_status_update', methods: ['PATCH'])]
    public function updateTenantStatus(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $status = (string) ($payload['status'] ?? '');
        $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;

        if ($status === '') {
            throw new BadRequestHttpException('O campo "status" é obrigatório.');
        }

        $tenant = $this->adminService->updateTenantStatus(
            $user,
            $id,
            $status,
            $reason,
            $request->getClientIp()
        );

        return new JsonResponse([
            'id' => $tenant->getId(),
            'name' => $tenant->getName(),
            'status' => $tenant->getStatus(),
            'message' => sprintf('Status do tenant atualizado para %s com sucesso.', $tenant->getStatus()),
        ]);
    }

    #[Route('/saas/plans', name: 'saas_plans_create', methods: ['POST'])]
    public function createPlan(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $plan = $this->adminService->createSaaSPlan($user, $payload, $request->getClientIp());

        return new JsonResponse($this->serializePlan($plan), Response::HTTP_CREATED);
    }

    #[Route('/saas/plans/{id}', name: 'saas_plans_update', methods: ['PUT'])]
    public function updatePlan(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $plan = $this->adminService->updateSaaSPlan($user, $id, $payload, $request->getClientIp());

        return new JsonResponse($this->serializePlan($plan));
    }

    #[Route('/tenants/{id}/subscription', name: 'tenants_subscription_override', methods: ['PUT'])]
    public function overrideSubscription(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $planIdentifier = (string) ($payload['plan_code'] ?? $payload['plan_id'] ?? '');
        $reason = isset($payload['reason']) ? (string) $payload['reason'] : null;

        if ($planIdentifier === '') {
            throw new BadRequestHttpException('O código ou ID do plano é obrigatório.');
        }

        $sub = $this->adminService->overrideTenantSubscription(
            $user,
            $id,
            $planIdentifier,
            $reason,
            $request->getClientIp()
        );

        return new JsonResponse([
            'id' => $sub->getId(),
            'tenant_id' => $sub->getTenant()->getId(),
            'status' => $sub->getStatus(),
            'plan' => $this->serializePlan($sub->getPlan()),
            'started_at' => $sub->getStartedAt()->format(\DateTimeInterface::ATOM),
            'current_period_end' => $sub->getCurrentPeriodEnd()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/audit-logs', name: 'audit_logs_list', methods: ['GET'])]
    public function listAuditLogs(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $action = $request->query->getString('action') ?: null;
        $targetType = $request->query->getString('target_type') ?: null;
        $limit = min(100, max(1, $request->query->getInt('limit', 50)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $logs = $this->adminService->listAuditLogs($user, $action, $targetType, $limit, $offset);

        $items = array_map(function (PlatformAuditLog $log) {
            return [
                'id' => $log->getId(),
                'admin_id' => $log->getAdminUser()->getId(),
                'admin_name' => $log->getAdminUser()->getName(),
                'admin_email' => $log->getAdminUser()->getEmail(),
                'action' => $log->getAction(),
                'target_type' => $log->getTargetType(),
                'target_id' => $log->getTargetId(),
                'details' => $log->getDetails(),
                'ip_address' => $log->getIpAddress(),
                'created_at' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $logs);

        return new JsonResponse([
            'logs' => $items,
            'count' => count($items),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(SaaSPlan $p): array
    {
        return [
            'id' => $p->getId(),
            'code' => $p->getCode(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'price_monthly' => $p->getPriceMonthly(),
            'price_yearly' => $p->getPriceYearly(),
            'max_students' => $p->getMaxStudents(),
            'max_trainers' => $p->getMaxTrainers(),
            'status' => $p->getStatus(),
        ];
    }
}
