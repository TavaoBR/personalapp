<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\PlatformAuditLog;
use App\Domain\Entity\SaaSPlan;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TenantSubscription;
use App\Domain\Entity\User;
use App\Domain\Entity\WorkoutSession;
use App\Domain\Enum\MembershipStatus;
use App\Domain\Enum\TenantType;
use App\Domain\Enum\UserStatus;
use App\Infrastructure\Repository\PlatformAuditLogRepository;
use App\Infrastructure\Repository\SaaSPlanRepository;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TenantRepository;
use App\Infrastructure\Repository\TenantSubscriptionRepository;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PlatformAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TenantRepository $tenantRepository,
        private readonly UserRepository $userRepository,
        private readonly TenantMembershipRepository $membershipRepository,
        private readonly SaaSPlanRepository $planRepository,
        private readonly TenantSubscriptionRepository $subscriptionRepository,
        private readonly PlatformAuditLogRepository $auditLogRepository,
        private readonly SaaSBillingService $billingService
    ) {
    }

    public function assertPlatformAdmin(User $user): void
    {
        if (!$user->isPlatformAdmin()) {
            throw new AccessDeniedHttpException('Acesso restrito: apenas administradores da plataforma podem realizar esta operação.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(User $adminUser): array
    {
        $this->assertPlatformAdmin($adminUser);

        // Contagem de tenants
        $allTenants = $this->tenantRepository->findAll();
        $totalTenants = count($allTenants);
        $activeTenants = 0;
        $suspendedTenants = 0;
        $personalTenants = 0;
        $assessoriaTenants = 0;

        foreach ($allTenants as $t) {
            if ($t->getStatus() === 'SUSPENDED') {
                $suspendedTenants++;
            } else {
                $activeTenants++;
            }

            if ($t->getType() === TenantType::PERSONAL) {
                $personalTenants++;
            } else {
                $assessoriaTenants++;
            }
        }

        // Total de usuários ativos
        $totalUsers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.status = :st')
            ->setParameter('st', UserStatus::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        // Total de perfis de alunos
        $totalStudents = (int) $this->em->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(StudentProfile::class, 's')
            ->where('s.status = :st')
            ->setParameter('st', 'ACTIVE')
            ->getQuery()
            ->getSingleScalarResult();

        // Total de sessões de treino executadas
        $totalWorkouts = (int) $this->em->createQueryBuilder()
            ->select('COUNT(w.id)')
            ->from(WorkoutSession::class, 'w')
            ->where('w.status = :st')
            ->setParameter('st', 'COMPLETED')
            ->getQuery()
            ->getSingleScalarResult();

        // Receita estimada SaaS (MRR)
        $activeSubs = $this->subscriptionRepository->findBy(['status' => 'ACTIVE']);
        $estimatedMrr = 0.0;
        foreach ($activeSubs as $sub) {
            $estimatedMrr += (float) $sub->getPlan()->getPriceMonthly();
        }

        return [
            'tenants' => [
                'total' => $totalTenants,
                'active' => $activeTenants,
                'suspended' => $suspendedTenants,
                'personal' => $personalTenants,
                'assessoria' => $assessoriaTenants,
            ],
            'users' => [
                'total_active' => $totalUsers,
            ],
            'students' => [
                'total_active' => $totalStudents,
            ],
            'workouts' => [
                'total_completed' => $totalWorkouts,
            ],
            'financial' => [
                'estimated_mrr' => round($estimatedMrr, 2),
                'active_subscriptions' => count($activeSubs),
            ],
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listTenants(
        User $adminUser,
        ?string $status = null,
        ?string $search = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $this->assertPlatformAdmin($adminUser);

        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Tenant::class, 't')
            ->orderBy('t.createdAt', 'DESC');

        if ($status !== null && trim($status) !== '') {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', strtoupper(trim($status)));
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('LOWER(t.name) LIKE :search OR LOWER(t.document) LIKE :search')
                ->setParameter('search', '%' . strtolower(trim($search)) . '%');
        }

        $allMatching = $qb->getQuery()->getResult();
        $total = count($allMatching);

        $offset = max(0, ($page - 1) * $limit);
        $qb->setFirstResult($offset)->setMaxResults($limit);
        /** @var list<Tenant> $tenants */
        $tenants = $qb->getQuery()->getResult();

        $items = [];
        foreach ($tenants as $t) {
            $sub = $this->billingService->getActiveSubscription($t);
            $studentCount = (int) $this->em->createQueryBuilder()
                ->select('COUNT(s.id)')
                ->from(StudentProfile::class, 's')
                ->where('s.tenant = :tenant')
                ->andWhere('s.status = :st')
                ->setParameter('tenant', $t)
                ->setParameter('st', 'ACTIVE')
                ->getQuery()
                ->getSingleScalarResult();

            $items[] = [
                'id' => $t->getId(),
                'name' => $t->getName(),
                'type' => $t->getType()->value,
                'status' => $t->getStatus(),
                'document' => $t->getDocument(),
                'active_students' => $studentCount,
                'current_plan' => $sub !== null ? [
                    'code' => $sub->getPlan()->getCode(),
                    'name' => $sub->getPlan()->getName(),
                    'status' => $sub->getStatus(),
                ] : null,
                'created_at' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTenantDetail(User $adminUser, string $tenantId): array
    {
        $this->assertPlatformAdmin($adminUser);

        $tenant = $this->tenantRepository->findById($tenantId);
        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        $sub = $this->billingService->getActiveSubscription($tenant);
        $usage = $this->billingService->getUsage($tenant);

        $memberships = $this->membershipRepository->findBy(['tenant' => $tenant]);
        $membersData = [];
        foreach ($memberships as $m) {
            $membersData[] = [
                'id' => $m->getId(),
                'user_id' => $m->getUser()->getId(),
                'name' => $m->getUser()->getName(),
                'email' => $m->getUser()->getEmail(),
                'role' => $m->getRole()->value,
                'status' => $m->getStatus()->value,
            ];
        }

        return [
            'tenant' => [
                'id' => $tenant->getId(),
                'name' => $tenant->getName(),
                'type' => $tenant->getType()->value,
                'status' => $tenant->getStatus(),
                'document' => $tenant->getDocument(),
                'created_at' => $tenant->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $tenant->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'subscription' => $sub !== null ? [
                'id' => $sub->getId(),
                'status' => $sub->getStatus(),
                'plan_code' => $sub->getPlan()->getCode(),
                'plan_name' => $sub->getPlan()->getName(),
                'started_at' => $sub->getStartedAt()->format(\DateTimeInterface::ATOM),
                'current_period_end' => $sub->getCurrentPeriodEnd()->format(\DateTimeInterface::ATOM),
            ] : null,
            'usage' => $usage,
            'members' => $membersData,
        ];
    }

    public function updateTenantStatus(
        User $adminUser,
        string $tenantId,
        string $newStatus,
        ?string $reason = null,
        ?string $ipAddress = null
    ): Tenant {
        $this->assertPlatformAdmin($adminUser);

        $tenant = $this->tenantRepository->findById($tenantId);
        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        $normalizedStatus = strtoupper(trim($newStatus));
        if (!in_array($normalizedStatus, ['ACTIVE', 'SUSPENDED', 'CANCELLED'], true)) {
            throw new BadRequestHttpException('Status de tenant inválido. Permitidos: ACTIVE, SUSPENDED, CANCELLED.');
        }

        $oldStatus = $tenant->getStatus();
        $tenant->setStatus($normalizedStatus);
        $this->tenantRepository->save($tenant);

        // Registro de Auditoria
        $action = $normalizedStatus === 'SUSPENDED' ? 'TENANT_SUSPENDED' : 'TENANT_STATUS_UPDATED';
        $auditLog = new PlatformAuditLog(
            $adminUser,
            $action,
            'Tenant',
            $tenant->getId(),
            [
                'tenant_name' => $tenant->getName(),
                'old_status' => $oldStatus,
                'new_status' => $normalizedStatus,
                'reason' => $reason,
            ],
            $ipAddress
        );
        $this->auditLogRepository->save($auditLog);

        return $tenant;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSaaSPlan(User $adminUser, array $data, ?string $ipAddress = null): SaaSPlan
    {
        $this->assertPlatformAdmin($adminUser);

        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));

        if ($code === '' || $name === '') {
            throw new BadRequestHttpException('Código e nome do plano SaaS são obrigatórios.');
        }

        if ($this->planRepository->findByCode($code) !== null) {
            throw new BadRequestHttpException(sprintf('Já existe um plano com o código "%s".', $code));
        }

        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $priceMonthly = (float) ($data['price_monthly'] ?? 0.0);
        $priceYearly = (float) ($data['price_yearly'] ?? 0.0);
        $maxStudents = (int) ($data['max_students'] ?? 10);
        $maxTrainers = (int) ($data['max_trainers'] ?? 1);
        $status = (string) ($data['status'] ?? 'ACTIVE');

        $plan = new SaaSPlan(
            $code,
            $name,
            $priceMonthly,
            $priceYearly,
            $maxStudents,
            $maxTrainers,
            $description,
            $status
        );

        $this->planRepository->save($plan);

        $auditLog = new PlatformAuditLog(
            $adminUser,
            'SAAS_PLAN_CREATED',
            'SaaSPlan',
            $plan->getId(),
            [
                'code' => $plan->getCode(),
                'name' => $plan->getName(),
                'price_monthly' => $priceMonthly,
                'max_students' => $maxStudents,
            ],
            $ipAddress
        );
        $this->auditLogRepository->save($auditLog);

        return $plan;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSaaSPlan(User $adminUser, string $planId, array $data, ?string $ipAddress = null): SaaSPlan
    {
        $this->assertPlatformAdmin($adminUser);

        $plan = $this->planRepository->find($planId);
        if ($plan === null) {
            $plan = $this->planRepository->findByCode($planId);
        }

        if ($plan === null) {
            throw new NotFoundHttpException('Plano SaaS não encontrado.');
        }

        if (isset($data['name'])) {
            $plan->setName(trim((string) $data['name']));
        }
        if (isset($data['description'])) {
            $plan->setDescription(trim((string) $data['description']));
        }
        if (isset($data['price_monthly'])) {
            $plan->setPriceMonthly((float) $data['price_monthly']);
        }
        if (isset($data['price_yearly'])) {
            $plan->setPriceYearly((float) $data['price_yearly']);
        }
        if (isset($data['max_students'])) {
            $plan->setMaxStudents((int) $data['max_students']);
        }
        if (isset($data['max_trainers'])) {
            $plan->setMaxTrainers((int) $data['max_trainers']);
        }
        if (isset($data['status'])) {
            $plan->setStatus(strtoupper(trim((string) $data['status'])));
        }

        $this->planRepository->save($plan);

        $auditLog = new PlatformAuditLog(
            $adminUser,
            'SAAS_PLAN_UPDATED',
            'SaaSPlan',
            $plan->getId(),
            [
                'code' => $plan->getCode(),
                'changes' => $data,
            ],
            $ipAddress
        );
        $this->auditLogRepository->save($auditLog);

        return $plan;
    }

    public function overrideTenantSubscription(
        User $adminUser,
        string $tenantId,
        string $planIdentifier,
        ?string $reason = null,
        ?string $ipAddress = null
    ): TenantSubscription {
        $this->assertPlatformAdmin($adminUser);

        $tenant = $this->tenantRepository->findById($tenantId);
        if ($tenant === null) {
            throw new NotFoundHttpException('Tenant não encontrado.');
        }

        $plan = $this->planRepository->findByCode($planIdentifier);
        if ($plan === null) {
            $plan = $this->planRepository->find($planIdentifier);
        }

        if ($plan === null) {
            throw new NotFoundHttpException('Plano SaaS não encontrado.');
        }

        $sub = $this->billingService->upgradeSubscription($tenant, $plan);

        $auditLog = new PlatformAuditLog(
            $adminUser,
            'SUBSCRIPTION_OVERRIDDEN',
            'TenantSubscription',
            $sub->getId(),
            [
                'tenant_id' => $tenant->getId(),
                'tenant_name' => $tenant->getName(),
                'plan_code' => $plan->getCode(),
                'reason' => $reason,
            ],
            $ipAddress
        );
        $this->auditLogRepository->save($auditLog);

        return $sub;
    }

    /**
     * @return list<PlatformAuditLog>
     */
    public function listAuditLogs(
        User $adminUser,
        ?string $action = null,
        ?string $targetType = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $this->assertPlatformAdmin($adminUser);

        return $this->auditLogRepository->findByFilters($action, $targetType, $limit, $offset);
    }
}
