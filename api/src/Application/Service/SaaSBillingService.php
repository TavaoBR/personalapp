<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Feature;
use App\Domain\Entity\PlanFeature;
use App\Domain\Entity\SaaSPlan;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantSubscription;
use App\Infrastructure\Repository\FeatureRepository;
use App\Infrastructure\Repository\SaaSPlanRepository;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TenantSubscriptionRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SaaSBillingService
{
    public function __construct(
        private readonly SaaSPlanRepository $planRepository,
        private readonly FeatureRepository $featureRepository,
        private readonly TenantSubscriptionRepository $subscriptionRepository,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerRepository
    ) {
    }

    public function createPlan(
        string $code,
        string $name,
        float $priceMonthly,
        float $priceYearly,
        ?int $maxStudents = null,
        ?int $maxTrainers = null,
        ?string $description = null
    ): SaaSPlan {
        $existing = $this->planRepository->findByCode($code);
        if ($existing !== null) {
            return $existing;
        }

        $plan = new SaaSPlan(
            code: $code,
            name: $name,
            priceMonthly: $priceMonthly,
            priceYearly: $priceYearly,
            maxStudents: $maxStudents,
            maxTrainers: $maxTrainers,
            description: $description
        );

        $this->planRepository->save($plan);

        return $plan;
    }

    public function createFeature(string $code, string $name, ?string $description = null): Feature
    {
        $existing = $this->featureRepository->findByCode($code);
        if ($existing !== null) {
            return $existing;
        }

        $feature = new Feature($code, $name, $description);
        $this->featureRepository->save($feature);

        return $feature;
    }

    public function assignFeatureToPlan(SaaSPlan $plan, Feature $feature, ?int $limitValue = null): PlanFeature
    {
        $planFeature = new PlanFeature($plan, $feature, $limitValue);
        $plan->addFeature($planFeature);
        $this->planRepository->save($plan);

        return $planFeature;
    }

    /**
     * @return array<SaaSPlan>
     */
    public function listActivePlans(): array
    {
        return $this->planRepository->findActivePlans();
    }

    public function getPlanById(string $id): ?SaaSPlan
    {
        return $this->planRepository->find($id);
    }

    public function getPlanByCode(string $code): ?SaaSPlan
    {
        return $this->planRepository->findByCode($code);
    }

    public function subscribeTenant(
        Tenant $tenant,
        SaaSPlan $plan,
        ?DateTimeImmutable $startDate = null,
        string $status = 'ACTIVE'
    ): TenantSubscription {
        $start = $startDate ?? new DateTimeImmutable();
        $end = $start->modify('+30 days');

        $subscription = new TenantSubscription(
            tenant: $tenant,
            plan: $plan,
            status: $status,
            startedAt: $start,
            currentPeriodStart: $start,
            currentPeriodEnd: $end
        );

        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }

    public function getActiveSubscription(Tenant $tenant): ?TenantSubscription
    {
        return $this->subscriptionRepository->findActiveByTenant($tenant);
    }

    public function upgradeSubscription(Tenant $tenant, SaaSPlan $newPlan): TenantSubscription
    {
        $current = $this->getActiveSubscription($tenant);
        if ($current === null) {
            return $this->subscribeTenant($tenant, $newPlan);
        }

        $current->setPlan($newPlan);
        $this->subscriptionRepository->save($current);

        return $current;
    }

    public function cancelSubscription(Tenant $tenant): TenantSubscription
    {
        $current = $this->getActiveSubscription($tenant);
        if ($current === null) {
            throw new NotFoundHttpException('Nenhuma assinatura ativa encontrada para este tenant.');
        }

        $current->cancel();
        $this->subscriptionRepository->save($current);

        return $current;
    }

    public function hasEntitlement(Tenant $tenant, string $featureCode): bool
    {
        $sub = $this->getActiveSubscription($tenant);
        if ($sub === null) {
            return false;
        }

        return $sub->getPlan()->hasFeature($featureCode);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsage(Tenant $tenant): array
    {
        $sub = $this->getActiveSubscription($tenant);
        $plan = $sub?->getPlan();

        $currentStudents = count($this->studentRepository->findByTenant($tenant));
        $currentTrainers = count($this->trainerRepository->findByTenant($tenant));

        $maxStudents = $plan?->getMaxStudents();
        $maxTrainers = $plan?->getMaxTrainers();

        $features = [];
        if ($plan) {
            foreach ($plan->getFeatures() as $pf) {
                $features[] = [
                    'code' => $pf->getFeature()->getCode(),
                    'name' => $pf->getFeature()->getName(),
                    'limit' => $pf->getLimitValue(),
                ];
            }
        }

        return [
            'tenant_id' => $tenant->getId(),
            'subscription_status' => $sub?->getStatus() ?? 'NONE',
            'plan_code' => $plan?->getCode() ?? 'NONE',
            'plan_name' => $plan?->getName() ?? 'Sem Plano Ativo',
            'students' => [
                'current' => $currentStudents,
                'max' => $maxStudents,
                'reached' => $maxStudents !== null && $currentStudents >= $maxStudents,
            ],
            'trainers' => [
                'current' => $currentTrainers,
                'max' => $maxTrainers,
                'reached' => $maxTrainers !== null && $currentTrainers >= $maxTrainers,
            ],
            'features' => $features,
        ];
    }

    public function assertCanAddStudent(Tenant $tenant): void
    {
        $sub = $this->getActiveSubscription($tenant);
        if ($sub === null) {
            return; // Sem enforcement se não houver restrição configurada
        }

        $max = $sub->getPlan()->getMaxStudents();
        if ($max === null) {
            return; // Ilimitado
        }

        $current = count($this->studentRepository->findByTenant($tenant));
        if ($current >= $max) {
            throw new AccessDeniedHttpException("Limite de alunos atingido ({$current}/{$max}) para o plano atual do tenant. Faça um upgrade para continuar.");
        }
    }

    public function assertCanAddTrainer(Tenant $tenant): void
    {
        $sub = $this->getActiveSubscription($tenant);
        if ($sub === null) {
            return;
        }

        $max = $sub->getPlan()->getMaxTrainers();
        if ($max === null) {
            return;
        }

        $current = count($this->trainerRepository->findByTenant($tenant));
        if ($current >= $max) {
            throw new AccessDeniedHttpException("Limite de treinadores atingido ({$current}/{$max}) para o plano atual do tenant. Faça um upgrade para continuar.");
        }
    }
}
