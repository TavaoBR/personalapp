<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Security\TenantAuthorizationService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        $items = array_map(fn ($s) => [
            'id' => $s->getId(),
            'name' => $s->getUser()->getName(),
            'email' => $s->getUser()->getEmail(),
            'gender' => $s->getGender(),
            'birth_date' => $s->getBirthDate()?->format('Y-m-d'),
            'status' => $s->getStatus(),
        ], $students);

        return new JsonResponse(['students' => $items]);
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
            'created_at' => $student->getCreatedAt()->format(\DateTimeInterface::ATOM),
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
