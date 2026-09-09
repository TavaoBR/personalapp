<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\TrainerStudentAssignment;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TenantRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use App\Infrastructure\Repository\TrainerStudentAssignmentRepository;
use App\Infrastructure\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class TenantIdentityService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly TenantRepository $tenantRepository,
        private readonly TenantMembershipRepository $membershipRepository,
        private readonly TrainerProfileRepository $trainerProfileRepository,
        private readonly StudentProfileRepository $studentProfileRepository,
        private readonly TrainerStudentAssignmentRepository $assignmentRepository,
    ) {
    }

    /**
     * Cria um novo Tenant e vincula o usuário criador como OWNER do tenant.
     *
     * @return array{tenant: Tenant, user: User, membership: TenantMembership}
     */
    public function createTenantWithOwner(
        string $tenantName,
        TenantType $tenantType,
        string $ownerName,
        string $ownerEmail,
        string $passwordHash
    ): array {
        $user = $this->userRepository->findByEmail($ownerEmail);
        if ($user === null) {
            $user = new User($ownerEmail, $passwordHash, $ownerName);
            $this->userRepository->save($user, false);
        }

        $tenant = new Tenant($tenantName, $tenantType);
        $this->tenantRepository->save($tenant, false);

        $membership = new TenantMembership($user, $tenant, TenantRole::OWNER);
        $this->membershipRepository->save($membership, false);

        // Se for personal ou treinador responsável, cria perfil técnico do treinador
        $trainerProfile = new TrainerProfile($user, $tenant);
        $this->trainerProfileRepository->save($trainerProfile, false);

        $this->entityManager->flush();

        return [
            'tenant' => $tenant,
            'user' => $user,
            'membership' => $membership,
            'trainerProfile' => $trainerProfile,
        ];
    }

    /**
     * Matricula um aluno em um Tenant com isolamento.
     */
    public function enrollStudent(
        Tenant $tenant,
        string $studentName,
        string $studentEmail,
        string $passwordHash,
        ?DateTimeImmutable $birthDate = null,
        ?string $gender = null
    ): StudentProfile {
        $user = $this->userRepository->findByEmail($studentEmail);
        if ($user === null) {
            $user = new User($studentEmail, $passwordHash, $studentName);
            $this->userRepository->save($user, false);
        }

        // Verifica membership no tenant
        $membership = $this->membershipRepository->findByUserAndTenant($user, $tenant);
        if ($membership === null) {
            $membership = new TenantMembership($user, $tenant, TenantRole::STUDENT);
            $this->membershipRepository->save($membership, false);
        }

        // Verifica perfil de aluno no tenant
        $profile = $this->studentProfileRepository->findByUserAndTenant($user, $tenant);
        if ($profile !== null) {
            throw new InvalidArgumentException('Aluno já cadastrado neste tenant.');
        }

        $profile = new StudentProfile($user, $tenant, null, $birthDate, $gender);
        $this->studentProfileRepository->save($profile, false);

        $this->entityManager->flush();

        return $profile;
    }

    /**
     * Vincula um aluno a um treinador dentro de uma assessoria.
     */
    public function assignTrainerToStudent(
        Tenant $tenant,
        TrainerProfile $trainer,
        StudentProfile $student,
        bool $isPrimary = true
    ): TrainerStudentAssignment {
        $existing = $this->assignmentRepository->findAssignment($tenant, $trainer, $student);
        if ($existing !== null) {
            return $existing;
        }

        $assignment = new TrainerStudentAssignment($tenant, $trainer, $student, $isPrimary);
        $this->assignmentRepository->save($assignment, true);

        return $assignment;
    }
}
