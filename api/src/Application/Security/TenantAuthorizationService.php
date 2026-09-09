<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Application\Service\TenantContext;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Security\Permission;
use App\Domain\Security\RolePermissionMap;
use App\Infrastructure\Repository\TrainerProfileRepository;
use App\Infrastructure\Repository\TrainerStudentAssignmentRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantAuthorizationService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrainerProfileRepository $trainerProfileRepository,
        private readonly TrainerStudentAssignmentRepository $assignmentRepository,
    ) {
    }

    public function hasPermission(Permission $permission): bool
    {
        $role = $this->tenantContext->getRole();
        if ($role === null) {
            return false;
        }

        return RolePermissionMap::hasPermission($role, $permission);
    }

    public function requirePermission(Permission $permission): void
    {
        if (!$this->hasPermission($permission)) {
            throw new AccessDeniedHttpException('Você não possui permissão para executar esta ação.');
        }
    }

    public function assertHasPermission(User|Permission $userOrPermission, ?Permission $permission = null): void
    {
        $perm = $permission ?? ($userOrPermission instanceof Permission ? $userOrPermission : null);
        if ($perm !== null) {
            $this->requirePermission($perm);
        }
    }

    /**
     * Avalia se o usuário autenticado pode acessar o perfil do aluno no tenant atual.
     * Retorna false se o aluno pertencer a outro tenant ou não estiver no escopo do usuário.
     */
    public function canAccessStudent(User $user, StudentProfile $student): bool
    {
        $currentTenant = $this->tenantContext->getTenant();
        if ($currentTenant === null) {
            return false;
        }

        // 1. Isolamento estrito de tenant
        if ($student->getTenant()->getId() !== $currentTenant->getId()) {
            return false;
        }

        $role = $this->tenantContext->getRole();

        // 2. OWNER, ADMIN e MANAGER têm acesso a todos os alunos do tenant
        if (in_array($role, [TenantRole::OWNER, TenantRole::ADMIN, TenantRole::MANAGER], true)) {
            return true;
        }

        // 3. STUDENT pode acessar somente o seu próprio perfil de aluno
        if ($role === TenantRole::STUDENT) {
            return $student->getUser()->getId() === $user->getId();
        }

        // 4. TRAINER pode acessar somente alunos atribuídos a ele na assessoria
        if ($role === TenantRole::TRAINER) {
            $trainerProfile = $this->trainerProfileRepository->findByUserAndTenant($user, $currentTenant);
            if ($trainerProfile === null) {
                return false;
            }

            $assignment = $this->assignmentRepository->findAssignment($currentTenant, $trainerProfile, $student);
            return $assignment !== null;
        }

        return false;
    }

    /**
     * Garante o acesso ao aluno ou lança 404 Not Found conforme docs/16-SEGURANCA.md.
     */
    public function assertCanAccessStudent(User $user, StudentProfile $student): void
    {
        if (!$this->canAccessStudent($user, $student)) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }
    }
}
