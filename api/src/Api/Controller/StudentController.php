<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\SaaSBillingService;
use App\Application\Service\TenantContext;
use App\Application\Service\TenantIdentityService;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use App\Infrastructure\Repository\TrainerStudentAssignmentRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/students', name: 'api_students_')]
final class StudentController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthorizationService $authorizationService,
        private readonly StudentProfileRepository $studentRepository,
        private readonly TrainerProfileRepository $trainerProfileRepository,
        private readonly TrainerStudentAssignmentRepository $assignmentRepository,
        private readonly TenantIdentityService $identityService,
        private readonly SaaSBillingService $billingService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $role = $this->tenantContext->getRole();

        if ($role === TenantRole::STUDENT) {
            throw new AccessDeniedHttpException('Alunos não têm permissão para listar a base de alunos.');
        }

        if (in_array($role, [TenantRole::OWNER, TenantRole::ADMIN, TenantRole::MANAGER], true)) {
            $students = $this->studentRepository->findByTenant($tenant);
        } elseif ($role === TenantRole::TRAINER) {
            $trainerProfile = $this->trainerProfileRepository->findByUserAndTenant($user, $tenant);
            $students = $trainerProfile !== null
                ? $this->studentRepository->findAssignedToTrainer($tenant, $trainerProfile)
                : [];
        } else {
            $students = [];
        }

        $items = array_map(function ($s) use ($tenant) {
            $assignments = $this->assignmentRepository->findActiveByStudent($tenant, $s);
            $activeTrainer = !empty($assignments) ? $assignments[0]->getTrainer() : null;

            return [
                'id' => $s->getId(),
                'name' => $s->getUser()->getName(),
                'email' => $s->getUser()->getEmail(),
                'phone' => $s->getUser()->getPhone(),
                'gender' => $s->getGender(),
                'birth_date' => $s->getBirthDate()?->format('Y-m-d'),
                'status' => $s->getStatus(),
                'trainer' => $activeTrainer !== null ? [
                    'id' => $activeTrainer->getId(),
                    'name' => $activeTrainer->getUser()->getName(),
                    'email' => $activeTrainer->getUser()->getEmail(),
                ] : null,
                'created_at' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $students);

        return new JsonResponse(['students' => $items]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $role = $this->tenantContext->getRole();

        if (!in_array($role, [TenantRole::OWNER, TenantRole::ADMIN, TenantRole::MANAGER], true)) {
            throw new AccessDeniedHttpException('Você não possui permissão para matricular novos alunos.');
        }

        $this->billingService->assertCanAddStudent($tenant);

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim(strtolower((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? 'Student@123456');
        $gender = isset($data['gender']) ? (string) $data['gender'] : null;
        $birthDateStr = isset($data['birth_date']) ? (string) $data['birth_date'] : null;
        $phone = isset($data['phone']) ? trim((string) $data['phone']) : null;
        $emergencyName = isset($data['emergency_contact_name']) ? trim((string) $data['emergency_contact_name']) : null;
        $emergencyPhone = isset($data['emergency_contact_phone']) ? trim((string) $data['emergency_contact_phone']) : null;
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
        $trainerId = isset($data['trainer_id']) ? trim((string) $data['trainer_id']) : null;

        if ($name === '' || $email === '') {
            return new JsonResponse([
                'error' => 'Dados incompletos',
                'message' => 'Nome e e-mail do aluno são obrigatórios.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $birthDate = null;
        if ($birthDateStr !== null && $birthDateStr !== '') {
            try {
                $birthDate = new DateTimeImmutable($birthDateStr);
            } catch (\Exception) {
                // Ignore parse errors, leave null
            }
        }

        $dummyUser = new User($email, 'temp', $name);
        $passwordHash = $this->passwordHasher->hashPassword($dummyUser, $password);

        $student = $this->identityService->enrollStudent(
            $tenant,
            $name,
            $email,
            $passwordHash,
            $birthDate,
            $gender
        );

        if ($phone !== null) {
            $student->getUser()->setPhone($phone);
        }
        if ($emergencyName !== null) {
            $student->setEmergencyContactName($emergencyName);
        }
        if ($emergencyPhone !== null) {
            $student->setEmergencyContactPhone($emergencyPhone);
        }
        if ($notes !== null) {
            $student->setNotes($notes);
        }
        $this->studentRepository->save($student, true);

        $trainerData = null;
        if ($trainerId !== null && $trainerId !== '') {
            $trainer = $this->trainerProfileRepository->find($trainerId);
            if ($trainer !== null && $trainer->getTenant()->getId() === $tenant->getId()) {
                $this->identityService->assignTrainerToStudent($tenant, $trainer, $student, true);
                $trainerData = [
                    'id' => $trainer->getId(),
                    'name' => $trainer->getUser()->getName(),
                ];
            }
        }

        return new JsonResponse([
            'id' => $student->getId(),
            'name' => $student->getUser()->getName(),
            'email' => $student->getUser()->getEmail(),
            'phone' => $student->getUser()->getPhone(),
            'gender' => $student->getGender(),
            'birth_date' => $student->getBirthDate()?->format('Y-m-d'),
            'emergency_contact_name' => $student->getEmergencyContactName(),
            'emergency_contact_phone' => $student->getEmergencyContactPhone(),
            'notes' => $student->getNotes(),
            'status' => $student->getStatus(),
            'trainer' => $trainerData,
            'created_at' => $student->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    public function detail(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);

        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        // Valida se o usuário tem autorização para visualizar este aluno específico
        $this->authorizationService->assertCanAccessStudent($user, $student);

        $assignments = $this->assignmentRepository->findActiveByStudent($tenant, $student);
        $activeTrainer = !empty($assignments) ? $assignments[0]->getTrainer() : null;

        return new JsonResponse([
            'id' => $student->getId(),
            'user' => [
                'id' => $student->getUser()->getId(),
                'name' => $student->getUser()->getName(),
                'email' => $student->getUser()->getEmail(),
                'phone' => $student->getUser()->getPhone(),
            ],
            'birth_date' => $student->getBirthDate()?->format('Y-m-d'),
            'gender' => $student->getGender(),
            'emergency_contact_name' => $student->getEmergencyContactName(),
            'emergency_contact_phone' => $student->getEmergencyContactPhone(),
            'notes' => $student->getNotes(),
            'status' => $student->getStatus(),
            'trainer' => $activeTrainer !== null ? [
                'id' => $activeTrainer->getId(),
                'name' => $activeTrainer->getUser()->getName(),
                'email' => $activeTrainer->getUser()->getEmail(),
            ] : null,
            'created_at' => $student->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/{id}/assign-trainer', name: 'assign_trainer', methods: ['POST'])]
    public function assignTrainer(string $id, Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $role = $this->tenantContext->getRole();

        if (!in_array($role, [TenantRole::OWNER, TenantRole::ADMIN, TenantRole::MANAGER], true)) {
            throw new AccessDeniedHttpException('Apenas gestores podem atribuir treinadores a alunos.');
        }

        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);
        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $trainerId = trim((string) ($data['trainer_id'] ?? ''));
        $isPrimary = (bool) ($data['is_primary'] ?? true);

        if ($trainerId === '') {
            return new JsonResponse([
                'error' => 'Dados incompletos',
                'message' => 'O trainer_id é obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $trainer = $this->trainerProfileRepository->find($trainerId);
        if ($trainer === null || $trainer->getTenant()->getId() !== $tenant->getId()) {
            throw new NotFoundHttpException('Treinador não encontrado ou não pertence a este tenant.');
        }

        $assignment = $this->identityService->assignTrainerToStudent($tenant, $trainer, $student, $isPrimary);

        return new JsonResponse([
            'message' => 'Treinador atribuído com sucesso ao aluno.',
            'assignment' => [
                'id' => $assignment->getId(),
                'student_id' => $student->getId(),
                'trainer_id' => $trainer->getId(),
                'trainer_name' => $trainer->getUser()->getName(),
                'is_primary' => $assignment->isPrimary(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $student = $this->studentRepository->findByIdAndTenant($id, $tenant);

        if ($student === null) {
            throw new NotFoundHttpException('Aluno não encontrado.');
        }

        $this->authorizationService->assertCanAccessStudent($user, $student);

        $data = json_decode((string) $request->getContent(), true) ?: [];

        if (array_key_exists('gender', $data)) {
            $student->setGender($data['gender'] !== null ? (string) $data['gender'] : null);
        }
        if (array_key_exists('emergencyContactName', $data)) {
            $student->setEmergencyContactName($data['emergencyContactName'] !== null ? (string) $data['emergencyContactName'] : null);
        }
        if (array_key_exists('emergencyContactPhone', $data)) {
            $student->setEmergencyContactPhone($data['emergencyContactPhone'] !== null ? (string) $data['emergencyContactPhone'] : null);
        }
        if (array_key_exists('notes', $data)) {
            $student->setNotes($data['notes'] !== null ? (string) $data['notes'] : null);
        }

        $this->studentRepository->save($student, true);

        return new JsonResponse([
            'id' => $student->getId(),
            'emergency_contact_name' => $student->getEmergencyContactName(),
            'emergency_contact_phone' => $student->getEmergencyContactPhone(),
            'notes' => $student->getNotes(),
            'status' => $student->getStatus(),
        ]);
    }
}
