<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\SaaSBillingService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\PlanFeature;
use App\Domain\Entity\SaaSPlan;
use App\Domain\Entity\TenantSubscription;
use App\Domain\Entity\User;
use App\Domain\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SaaSBillingController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly SaaSBillingService $billingService
    ) {
    }

    #[Route('/api/saas/plans', name: 'api_saas_plans_list', methods: ['GET'])]
    public function listPlans(): JsonResponse
    {
        $plans = $this->billingService->listActivePlans();
        $data = array_map(fn (SaaSPlan $p) => $this->serializePlan($p), $plans);

        return new JsonResponse(['plans' => $data]);
    }

    #[Route('/api/saas/plans/{id}', name: 'api_saas_plans_detail', methods: ['GET'])]
    public function planDetail(string $id): JsonResponse
    {
        $plan = $this->billingService->getPlanById($id);
        if ($plan === null) {
            $plan = $this->billingService->getPlanByCode($id);
        }

        if ($plan === null) {
            throw new NotFoundHttpException('Plano SaaS não encontrado.');
        }

        return new JsonResponse($this->serializePlan($plan));
    }

    #[Route('/api/saas/subscription', name: 'api_saas_subscription_get', methods: ['GET'])]
    public function getSubscription(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_TENANT);

        $sub = $this->billingService->getActiveSubscription($tenant);
        if ($sub === null) {
            return new JsonResponse([
                'subscription' => null,
                'message' => 'Nenhuma assinatura ativa encontrada para este tenant.',
            ]);
        }

        return new JsonResponse([
            'subscription' => $this->serializeSubscription($sub),
        ]);
    }

    #[Route('/api/saas/subscription', name: 'api_saas_subscription_create', methods: ['POST'])]
    public function subscribeOrUpgrade(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_TENANT);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        $planIdentifier = (string) ($data['plan_code'] ?? $data['plan_id'] ?? '');
        if ($planIdentifier === '') {
            throw new BadRequestHttpException('O código ou ID do plano SaaS é obrigatório.');
        }

        $plan = $this->billingService->getPlanByCode($planIdentifier);
        if ($plan === null) {
            $plan = $this->billingService->getPlanById($planIdentifier);
        }

        if ($plan === null) {
            throw new NotFoundHttpException('Plano SaaS não encontrado.');
        }

        $sub = $this->billingService->upgradeSubscription($tenant, $plan);

        return new JsonResponse($this->serializeSubscription($sub), Response::HTTP_CREATED);
    }

    #[Route('/api/saas/subscription/cancel', name: 'api_saas_subscription_cancel', methods: ['POST'])]
    public function cancelSubscription(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_TENANT);

        $sub = $this->billingService->cancelSubscription($tenant);

        return new JsonResponse($this->serializeSubscription($sub));
    }

    #[Route('/api/saas/usage', name: 'api_saas_usage_get', methods: ['GET'])]
    public function getUsage(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $this->authorizationService->assertHasPermission($user, Permission::MANAGE_TENANT);

        $usage = $this->billingService->getUsage($tenant);

        return new JsonResponse($usage);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(SaaSPlan $p): array
    {
        $features = [];
        foreach ($p->getFeatures() as $pf) {
            $features[] = [
                'code' => $pf->getFeature()->getCode(),
                'name' => $pf->getFeature()->getName(),
                'limit' => $pf->getLimitValue(),
            ];
        }

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
            'features' => $features,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSubscription(TenantSubscription $s): array
    {
        return [
            'id' => $s->getId(),
            'tenant_id' => $s->getTenant()->getId(),
            'status' => $s->getStatus(),
            'plan' => $this->serializePlan($s->getPlan()),
            'started_at' => $s->getStartedAt()->format(\DateTimeInterface::ATOM),
            'current_period_start' => $s->getCurrentPeriodStart()->format(\DateTimeInterface::ATOM),
            'current_period_end' => $s->getCurrentPeriodEnd()->format(\DateTimeInterface::ATOM),
            'cancel_at_period_end' => $s->isCancelAtPeriodEnd(),
            'canceled_at' => $s->getCanceledAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
