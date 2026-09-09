<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Service\TenantResolver;
use App\Application\Service\TokenService;
use App\Domain\Enum\UserStatus;
use App\Infrastructure\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly UserRepository $userRepository,
        private readonly TenantResolver $tenantResolver,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization');
        return $authHeader !== null && str_starts_with($authHeader, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = (string) $request->headers->get('Authorization');
        $rawToken = substr($authHeader, 7);

        $payload = $this->tokenService->decodeAccessToken($rawToken);
        if ($payload === null || !isset($payload['sub'])) {
            throw new CustomUserMessageAuthenticationException('Token de acesso inválido ou expirado.');
        }

        $userId = (string) $payload['sub'];
        // Resolução do Tenant: prioriza header X-Tenant-ID se enviado, senão usa claim tenant_id do token
        $tenantIdToResolve = $request->headers->get('X-Tenant-ID') ?: ($payload['tenant_id'] ?? null);

        return new SelfValidatingPassport(
            new UserBadge($userId, function (string $identifier) use ($tenantIdToResolve) {
                $user = $this->userRepository->find($identifier);
                if ($user === null || $user->getStatus() !== UserStatus::ACTIVE) {
                    throw new CustomUserMessageAuthenticationException('Usuário inativo ou inexistente.');
                }

                // Resolve o TenantContext para este request
                $this->tenantResolver->resolve($user, $tenantIdToResolve);

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Prossegue normalmente para a controller
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Não autorizado',
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
