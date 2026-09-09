<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthAndTenantContextTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    private function initializeDatabaseAndFixtures(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);

        // Reset schema
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // Create User
        $user = new User('coach@example.com', '', 'Coach Silva');
        $user->setPasswordHash($this->hasher->hashPassword($user, 'secret123'));
        $this->entityManager->persist($user);

        // Create Tenant A
        $tenantA = new Tenant('Assessoria Alpha', TenantType::ASSESSORIA);
        $this->entityManager->persist($tenantA);
        $membershipA = new TenantMembership($user, $tenantA, TenantRole::OWNER);
        $this->entityManager->persist($membershipA);

        // Create Tenant B
        $tenantB = new Tenant('Consultoria Beta', TenantType::PERSONAL);
        $this->entityManager->persist($tenantB);
        $membershipB = new TenantMembership($user, $tenantB, TenantRole::TRAINER);
        $this->entityManager->persist($membershipB);

        // Create Unrelated Tenant C (user does not belong to C)
        $otherUser = new User('stranger@example.com', 'hash', 'Stranger');
        $this->entityManager->persist($otherUser);
        $tenantC = new Tenant('Academia Gama', TenantType::ASSESSORIA);
        $this->entityManager->persist($tenantC);
        $membershipC = new TenantMembership($otherUser, $tenantC, TenantRole::OWNER);
        $this->entityManager->persist($membershipC);

        $this->entityManager->flush();

        return [
            'client' => $client,
            'user' => $user,
            'tenantA' => $tenantA,
            'tenantB' => $tenantB,
            'tenantC' => $tenantC,
        ];
    }

    public function testLoginSuccessReturnsTokensAndSetsHttpOnlyCookie(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'coach@example.com',
            'password' => 'secret123',
        ]));

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $data = json_decode((string) $response->getContent(), true);

        $this->assertArrayHasKey('access_token', $data);
        $this->assertSame('Bearer', $data['token_type']);
        $this->assertSame('Coach Silva', $data['user']['name']);
        $this->assertNotNull($data['active_tenant']);
        $this->assertCount(2, $data['available_tenants']);

        // Valida cookie HttpOnly de Refresh Token
        $cookies = $response->headers->getCookies();
        $refreshCookie = null;
        foreach ($cookies as $c) {
            if ($c->getName() === 'refreshToken') {
                $refreshCookie = $c;
                break;
            }
        }

        $this->assertNotNull($refreshCookie, 'Cookie refreshToken deve estar presente na resposta.');
        $this->assertTrue($refreshCookie->isHttpOnly(), 'Cookie refreshToken DEVE ser HttpOnly por segurança.');
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'coach@example.com',
            'password' => 'wrong_password',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedMeEndpointRequiresAuthentication(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];

        // Request sem token -> 401
        $client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);

        // Request com token inválido -> 401
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token_invalido_qualquer',
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAccessMeAndResolveTenantContext(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];
        $tenantB = $fixtures['tenantB'];
        $tenantC = $fixtures['tenantC'];

        // 1. Fazer login
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'coach@example.com',
            'password' => 'secret123',
        ]));
        $loginData = json_decode((string) $client->getResponse()->getContent(), true);
        $token = $loginData['access_token'];

        // 2. Chamar /api/me com o token válido
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $meData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('coach@example.com', $meData['user']['email']);
        $this->assertNotNull($meData['active_tenant']);

        // 3. Chamar /api/me com header X-Tenant-ID apontando para Tenant B
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            'HTTP_X_TENANT_ID' => $tenantB->getId(),
        ]);
        $this->assertResponseIsSuccessful();
        $meDataB = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($tenantB->getId(), $meDataB['active_tenant']['id']);
        $this->assertSame('TRAINER', $meDataB['active_tenant']['role']);

        // 4. Chamar /api/me com header X-Tenant-ID apontando para Tenant C (não autorizado!) -> 403
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            'HTTP_X_TENANT_ID' => $tenantC->getId(),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRefreshTokenRotatesAndLogoutRevokes(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];

        // 1. Login
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'coach@example.com',
            'password' => 'secret123',
        ]));
        $this->assertResponseIsSuccessful();

        // 2. Refresh usando o cookie recebido
        $client->request('POST', '/api/auth/refresh');
        $this->assertResponseIsSuccessful();
        $refreshData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $refreshData);

        // 3. Logout
        $client->request('POST', '/api/auth/logout');
        $this->assertResponseIsSuccessful();

        // 4. Tentativa de refresh após logout deve falhar com 401
        $client->request('POST', '/api/auth/refresh');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testSwitchTenantEndpoint(): void
    {
        $fixtures = $this->initializeDatabaseAndFixtures();
        $client = $fixtures['client'];
        $tenantB = $fixtures['tenantB'];

        // Login
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'email' => 'coach@example.com',
            'password' => 'secret123',
        ]));
        $token = json_decode((string) $client->getResponse()->getContent(), true)['access_token'];

        // Switch Tenant para Tenant B
        $client->request('POST', '/api/auth/switch-tenant', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'tenantId' => $tenantB->getId(),
        ]));

        $this->assertResponseIsSuccessful();
        $switchData = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $switchData);
        $this->assertSame($tenantB->getId(), $switchData['active_tenant']['id']);
        $this->assertSame('TRAINER', $switchData['active_tenant']['role']);
    }
}
