<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\User;
use DateTimeImmutable;
use InvalidArgumentException;

class TokenService
{
    private string $secret;
    private int $accessTokenTtl; // em segundos (padrão 15 min = 900s)

    public function __construct(string $secret, int $accessTokenTtl = 900)
    {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('APP_SECRET não pode ser vazio para geração de tokens.');
        }
        $this->secret = $secret;
        $this->accessTokenTtl = $accessTokenTtl;
    }

    /**
     * Gera um Access Token JWT assinado com HMAC-SHA256.
     */
    public function createAccessToken(
        User $user,
        ?Tenant $tenant = null,
        ?TenantMembership $membership = null
    ): string {
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'is_admin' => $user->isPlatformAdmin(),
            'tenant_id' => $tenant?->getId(),
            'role' => $membership?->getRole()->value,
            'iat' => $now,
            'exp' => $now + $this->accessTokenTtl,
        ];

        return $this->encodeJwt($payload);
    }

    /**
     * Valida e decodifica um token JWT. Retorna o payload decodificado ou null se inválido/expirado.
     *
     * @return array<string, mixed>|null
     */
    public function decodeAccessToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = $this->sign(sprintf('%s.%s', $headerB64, $payloadB64));
        if (!hash_equals($expectedSig, $this->base64UrlDecode($signatureB64))) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        // Verifica expiração
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Gera uma string criptograficamente segura para Refresh Token.
     */
    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodeJwt(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $headerB64 = $this->base64UrlEncode((string) json_encode($header));
        $payloadB64 = $this->base64UrlEncode((string) json_encode($payload));

        $signature = $this->sign(sprintf('%s.%s', $headerB64, $payloadB64));
        $sigB64 = $this->base64UrlEncode($signature);

        return sprintf('%s.%s.%s', $headerB64, $payloadB64, $sigB64);
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
