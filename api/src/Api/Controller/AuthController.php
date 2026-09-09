<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\Service\TenantContext;
use App\Application\Service\TenantResolver;
use App\Application\Service\TokenService;
use App\Domain\Entity\RefreshToken;
use App\Domain\Entity\User;
use App\Domain\Enum\MembershipStatus;
use App\Domain\Enum\UserStatus;
use App\Infrastructure\Repository\RefreshTokenRepository;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TenantMembershipRepository $membershipRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly TokenService $tokenService,
        private readonly TenantResolver $tenantResolver,
        private readonly TenantContext $tenantContext,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true) ?: [];
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $requestedTenantId = isset($data['tenantId']) ? trim((string) $data['tenantId']) : null;

        if ($email === '' || $password === '') {
            return new JsonResponse([
                'error' => 'Dados incompletos',
                'message' => 'Email e senha são obrigatórios.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || $user->getStatus() !== UserStatus::ACTIVE) {
            return new JsonResponse([
                'error' => 'Credenciais inválidas',
                'message' => 'Usuário ou senha incorretos.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'error' => 'Credenciais inválidas',
                'message' => 'Usuário ou senha incorretos.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Resolução de Tenant
        $resolvedContext = $this->tenantResolver->resolve($user, $requestedTenantId);
        $activeTenant = $resolvedContext->getTenant();
        $activeMembership = $resolvedContext->getMembership();

        // Emissão de Tokens
        $accessToken = $this->tokenService->createAccessToken($user, $activeTenant, $activeMembership);

        $plainRefreshToken = $this->tokenService->generateRefreshToken();
        $refreshTokenEntity = new RefreshToken(
            $user,
            $plainRefreshToken,
            new DateTimeImmutable('+30 days')
        );
        $this->refreshTokenRepository->save($refreshTokenEntity);

        // Lista de Tenants disponíveis para o usuário
        $allMemberships = $this->membershipRepository->findByUser($user);
        $availableTenants = [];
        foreach ($allMemberships as $m) {
            if ($m->getStatus() === MembershipStatus::ACTIVE) {
                $availableTenants[] = [
                    'id' => $m->getTenant()->getId(),
                    'name' => $m->getTenant()->getName(),
                    'type' => $m->getTenant()->getType()->value,
                    'role' => $m->getRole()->value,
                ];
            }
        }

        $response = new JsonResponse([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'is_platform_admin' => $user->isPlatformAdmin(),
            ],
            'active_tenant' => $activeTenant !== null ? [
                'id' => $activeTenant->getId(),
                'name' => $activeTenant->getName(),
                'type' => $activeTenant->getType()->value,
                'role' => $activeMembership?->getRole()->value,
            ] : null,
            'available_tenants' => $availableTenants,
        ]);

        // Configuração de cookie HttpOnly e Secure
        $isSecure = $request->isSecure() || $this->getParameter('kernel.environment') === 'prod';
        $response->headers->setCookie(
            Cookie::create(
                'refreshToken',
                $plainRefreshToken,
                new DateTimeImmutable('+30 days'),
                '/api/auth',
                null,
                $isSecure,
                true, // HttpOnly
                false,
                Cookie::SAMESITE_LAX
            )
        );

        return $response;
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $plainToken = $request->cookies->get('refreshToken');
        if ($plainToken === null || trim($plainToken) === '') {
            return new JsonResponse([
                'error' => 'Não autorizado',
                'message' => 'Cookie de atualização ausente ou inválido.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenEntity = $this->refreshTokenRepository->findValidByPlainToken($plainToken);
        if ($tokenEntity === null) {
            return new JsonResponse([
                'error' => 'Não autorizado',
                'message' => 'Token de atualização expirado ou revogado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $tokenEntity->getUser();
        if ($user->getStatus() !== UserStatus::ACTIVE) {
            return new JsonResponse([
                'error' => 'Não autorizado',
                'message' => 'Usuário inativo.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $explicitTenant = $request->headers->get('X-Tenant-ID');
        $resolvedContext = $this->tenantResolver->resolve($user, $explicitTenant);

        $newAccessToken = $this->tokenService->createAccessToken(
            $user,
            $resolvedContext->getTenant(),
            $resolvedContext->getMembership()
        );

        return new JsonResponse([
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $plainToken = $request->cookies->get('refreshToken');
        if ($plainToken !== null && trim($plainToken) !== '') {
            $tokenEntity = $this->refreshTokenRepository->findValidByPlainToken($plainToken);
            if ($tokenEntity !== null) {
                $tokenEntity->revoke();
                $this->refreshTokenRepository->save($tokenEntity);
            }
        }

        $response = new JsonResponse(['status' => 'logged_out']);
        $response->headers->clearCookie('refreshToken', '/api/auth');

        return $response;
    }

    #[Route('/switch-tenant', name: 'switch_tenant', methods: ['POST'])]
    public function switchTenant(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return new JsonResponse(['error' => 'Não autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $targetTenantId = trim((string) ($data['tenantId'] ?? ''));

        if ($targetTenantId === '') {
            return new JsonResponse([
                'error' => 'Dados incompletos',
                'message' => 'O tenantId é obrigatório.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $resolvedContext = $this->tenantResolver->resolve($user, $targetTenantId);
        $newTenant = $resolvedContext->requireTenant();
        $membership = $resolvedContext->getMembership();

        $newAccessToken = $this->tokenService->createAccessToken($user, $newTenant, $membership);

        return new JsonResponse([
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
            'active_tenant' => [
                'id' => $newTenant->getId(),
                'name' => $newTenant->getName(),
                'type' => $newTenant->getType()->value,
                'role' => $membership?->getRole()->value,
            ],
        ]);
    }
}
