<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Service\SaaSBillingService;
use App\Application\Service\TenantContext;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/trainers', name: 'api_trainers_')]
final class TrainerController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrainerProfileRepository $trainerRepository,
        private readonly UserRepository $userRepository,
        private readonly TenantMembershipRepository $membershipRepository,
        private readonly SaaSBillingService $billingService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $role = $this->tenantContext->getRole();

        if ($role === TenantRole::STUDENT) {
            throw new AccessDeniedHttpException('Alunos não têm permissão para listar a equipe de treinadores.');
        }

        $trainers = $this->trainerRepository->findByTenant($tenant);

        $data = array_map(fn (TrainerProfile $t) => [
            'id' => $t->getId(),
            'user' => [
                'id' => $t->getUser()->getId(),
                'name' => $t->getUser()->getName(),
                'email' => $t->getUser()->getEmail(),
                'phone' => $t->getUser()->getPhone(),
            ],
            'cref' => $t->getCref(),
            'specialties' => $t->getSpecialties(),
            'bio' => $t->getBio(),
            'created_at' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $trainers);

        return new JsonResponse(['trainers' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $role = $this->tenantContext->getRole();

        if (!in_array($role, [TenantRole::OWNER, TenantRole::ADMIN, TenantRole::MANAGER], true)) {
            throw new AccessDeniedHttpException('Apenas gestores e proprietários podem cadastrar treinadores.');
        }

        $this->billingService->assertCanAddTrainer($tenant);

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim(strtolower((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? 'Trainer@123456');
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : null;
        $cref = isset($payload['cref']) ? trim((string) $payload['cref']) : null;
        $specialties = is_array($payload['specialties'] ?? null) ? $payload['specialties'] : [];
        $bio = isset($payload['bio']) ? trim((string) $payload['bio']) : null;

        if ($name === '' || $email === '') {
            return new JsonResponse([
                'error' => 'Dados incompletos',
                'message' => 'Nome e e-mail do treinador são obrigatórios.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            $user = new User($email, 'temporary', $name, $phone);
            $hashed = $this->passwordHasher->hashPassword($user, $password);
            $user->setPasswordHash($hashed);
            $this->userRepository->save($user, false);
        } else {
            if ($phone !== null) {
                $user->setPhone($phone);
            }
        }

        $existingProfile = $this->trainerRepository->findByUserAndTenant($user, $tenant);
        if ($existingProfile !== null) {
            return new JsonResponse([
                'error' => 'Treinador já cadastrado',
                'message' => 'Este usuário já possui perfil de treinador neste tenant.',
            ], Response::HTTP_CONFLICT);
        }

        $membership = $this->membershipRepository->findByUserAndTenant($user, $tenant);
        if ($membership === null) {
            $membership = new TenantMembership($user, $tenant, TenantRole::TRAINER);
            $this->membershipRepository->save($membership, false);
        }

        $trainer = new TrainerProfile($user, $tenant, null, $cref, $specialties, $bio);
        $this->trainerRepository->save($trainer, false);

        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $trainer->getId(),
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
            ],
            'cref' => $trainer->getCref(),
            'specialties' => $trainer->getSpecialties(),
            'bio' => $trainer->getBio(),
            'created_at' => $trainer->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }
}
