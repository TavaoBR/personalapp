<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Enum\MembershipStatus;
use App\Infrastructure\Repository\TenantMembershipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
final class MeController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantMembershipRepository $membershipRepository,
    ) {
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $activeTenant = $this->tenantContext->getTenant();
        $activeRole = $this->tenantContext->getRole();

        $memberships = $this->membershipRepository->findByUser($user);
        $userMemberships = [];

        foreach ($memberships as $m) {
            if ($m->getStatus() === MembershipStatus::ACTIVE) {
                $userMemberships[] = [
                    'tenant_id' => $m->getTenant()->getId(),
                    'tenant_name' => $m->getTenant()->getName(),
                    'tenant_type' => $m->getTenant()->getType()->value,
                    'role' => $m->getRole()->value,
                ];
            }
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'status' => $user->getStatus()->value,
                'is_platform_admin' => $user->isPlatformAdmin(),
                'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'active_tenant' => $activeTenant !== null ? [
                'id' => $activeTenant->getId(),
                'name' => $activeTenant->getName(),
                'type' => $activeTenant->getType()->value,
                'role' => $activeRole?->value,
            ] : null,
            'memberships' => $userMemberships,
        ]);
    }
}
