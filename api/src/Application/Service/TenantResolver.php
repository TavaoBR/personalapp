<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\User;
use App\Domain\Enum\MembershipStatus;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TenantRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantResolver
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly TenantMembershipRepository $membershipRepository,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Resolve e valida o tenant ativo para o usuário.
     * Se um explicitTenantId for fornecido, valida se o usuário realmente pertence a ele com status ACTIVE.
     * Se não for fornecido, adota o primeiro tenant ativo do usuário (se existir).
     */
    public function resolve(User $user, ?string $explicitTenantId = null): TenantContext
    {
        if ($explicitTenantId !== null && trim($explicitTenantId) !== '') {
            $tenant = $this->tenantRepository->findById(trim($explicitTenantId));
            if ($tenant === null) {
                throw new NotFoundHttpException('Tenant não encontrado.');
            }

            if ($tenant->getStatus() === 'SUSPENDED' && !$user->isPlatformAdmin()) {
                throw new AccessDeniedHttpException('Acesso negado: esta organização/tenant está suspensa.');
            }

            $membership = $this->membershipRepository->findByUserAndTenant($user, $tenant);
            if ($membership === null || $membership->getStatus() !== MembershipStatus::ACTIVE) {
                if ($user->isPlatformAdmin()) {
                    $this->tenantContext->set($tenant, null);
                    return $this->tenantContext;
                }
                throw new AccessDeniedHttpException('Acesso negado: você não possui vínculo ativo com este tenant.');
            }

            $this->tenantContext->set($tenant, $membership);
            return $this->tenantContext;
        }

        // Caso nenhum tenant específico tenha sido requisitado, utiliza o primeiro ativo
        $memberships = $this->membershipRepository->findByUser($user);
        foreach ($memberships as $m) {
            if ($m->getStatus() === MembershipStatus::ACTIVE && ($m->getTenant()->getStatus() !== 'SUSPENDED' || $user->isPlatformAdmin())) {
                $this->tenantContext->set($m->getTenant(), $m);
                return $this->tenantContext;
            }
        }

        return $this->tenantContext;
    }
}
