<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Enum\TenantRole;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantContext
{
    private ?Tenant $tenant = null;
    private ?TenantMembership $membership = null;

    public function set(Tenant $tenant, TenantMembership $membership): void
    {
        $this->tenant = $tenant;
        $this->membership = $membership;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?string
    {
        return $this->tenant?->getId();
    }

    public function getMembership(): ?TenantMembership
    {
        return $this->membership;
    }

    public function getRole(): ?TenantRole
    {
        return $this->membership?->getRole();
    }

    public function isResolved(): bool
    {
        return $this->tenant !== null && $this->membership !== null;
    }

    /**
     * Retorna o Tenant ativo garantido ou lança 403 Forbidden caso a rota exija tenant.
     */
    public function requireTenant(): Tenant
    {
        if ($this->tenant === null) {
            throw new AccessDeniedHttpException('Contexto de Tenant obrigatório não resolvido para esta operação.');
        }

        return $this->tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->membership = null;
    }
}
