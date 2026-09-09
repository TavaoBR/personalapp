<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\SaaSBillingService;
use App\Application\Service\TenantIdentityService;
use App\Application\Service\TokenService;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SaaSBillingTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private TokenService $tokenService;
    private TenantIdentityService $identityService;
    private SaaSBillingService $billingService;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    private function setupScenario(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->tokenService = $container->get(TokenService::class);
        $this->identityService = $container->get(TenantIdentityService::class);
        $this->billingService = $container->get(SaaSBillingService::class);

        // Reset schema
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 1. Criar Features da Plataforma
        $featAnalytics = $this->billingService->createFeature('ANALYTICS_ADVANCED', 'Analytics Avançado');
        $featWhiteLabel = $this->billingService->createFeature('WHITE_LABEL', 'Customização Visual');

        // 2. Criar Planos SaaS Padrão
        $starterPlan = $this->billingService->createPlan(
            code: 'STARTER',
            name: 'Plano Starter',
            priceMonthly: 49.90,
            priceYearly: 499.00,
            maxStudents: 1, // Limite restrito para teste
            maxTrainers: 1,
            description: 'Para personal trainers iniciantes'
        );

        $proPlan = $this->billingService->createPlan(
            code: 'PRO',
            name: 'Plano Pro',
            priceMonthly: 129.90,
            priceYearly: 1299.00,
            maxStudents: 50,
            maxTrainers: 3,
            description: 'Para assessorias e personais estabelecidos'
        );
        $this->billingService->assignFeatureToPlan($proPlan, $featAnalytics);
        $this->billingService->assignFeatureToPlan($proPlan, $featWhiteLabel);

        // 3. Tenant Alpha
        $alphaData = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'pass123'
        );
        $tenantAlpha = $alphaData['tenant'];
        $ownerUser = $alphaData['user'];

        // Treinador Alpha
        $trainerUser = new User('trainer@alpha.com', 'pass123', 'Treinador Alpha');
        $this->entityManager->persist($trainerUser);
        $membershipTrainer = new TenantMembership($trainerUser, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipTrainer);
        $trainerProfile = new TrainerProfile($trainerUser, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile);

        // Aluno Alpha (já ocupa 1 vaga de aluno)
        $studentProfile = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha',
            'student@alpha.com',
            'pass123'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile, $studentProfile);

        $this->entityManager->flush();

        return [
            'client' => $client,
            'tenantAlpha' => $tenantAlpha,
            'ownerUser' => $ownerUser,
            'membershipOwner' => $alphaData['membership'],
            'trainerUser' => $trainerUser,
            'membershipTrainer' => $membershipTrainer,
            'starterPlan' => $starterPlan,
            'proPlan' => $proPlan,
        ];
    }

    private function getAuthHeaders(User $user, \App\Domain\Entity\Tenant $tenant, ?TenantMembership $membership = null): array
    {
        $token = $this->tokenService->createAccessToken($user, $tenant, $membership);
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $tenant->getId(),
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    public function testListSaaSPlans(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];

        $client->request('GET', '/api/saas/plans');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertCount(2, $res['plans']);
        $this->assertSame('STARTER', $res['plans'][0]['code']);
        $this->assertSame('PRO', $res['plans'][1]['code']);
        $this->assertCount(2, $res['plans'][1]['features']);
    }

    public function testTenantSubscribeAndEntitlements(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $tenant = $data['tenantAlpha'];

        // 1. Assina plano PRO
        $client->request('POST', '/api/saas/subscription', [], [], $ownerHeaders, json_encode([
            'plan_code' => 'PRO',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $sub = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('ACTIVE', $sub['status']);
        $this->assertSame('PRO', $sub['plan']['code']);

        // 2. Consulta assinatura ativa
        $client->request('GET', '/api/saas/subscription', [], [], $ownerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $currentSub = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('PRO', $currentSub['subscription']['plan']['code']);

        // 3. Valida entitlements via serviço
        $this->assertTrue($this->billingService->hasEntitlement($tenant, 'ANALYTICS_ADVANCED'));
        $this->assertTrue($this->billingService->hasEntitlement($tenant, 'WHITE_LABEL'));
        $this->assertFalse($this->billingService->hasEntitlement($tenant, 'API_ACCESS'));
    }

    public function testUsageLimitsAndEnforcement(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $tenant = $data['tenantAlpha'];

        // 1. Assina plano STARTER (maxStudents = 1)
        $client->request('POST', '/api/saas/subscription', [], [], $ownerHeaders, json_encode([
            'plan_code' => 'STARTER',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Consulta uso: atual 1 aluno, max 1 -> limite atingido
        $client->request('GET', '/api/saas/usage', [], [], $ownerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $usage = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $usage['students']['current']);
        $this->assertSame(1, $usage['students']['max']);
        $this->assertTrue($usage['students']['reached']);

        // 3. Tentar adicionar mais um aluno dispara AccessDeniedHttpException
        $this->expectException(AccessDeniedHttpException::class);
        $this->billingService->assertCanAddStudent($tenant);
    }

    public function testCancelSubscription(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);

        // 1. Assina plano PRO
        $client->request('POST', '/api/saas/subscription', [], [], $ownerHeaders, json_encode([
            'plan_code' => 'PRO',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Cancela assinatura
        $client->request('POST', '/api/saas/subscription/cancel', [], [], $ownerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('CANCELED', $res['status']);
        $this->assertNotNull($res['canceled_at']);
    }

    public function testSecurityTenantScope(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);

        // Treinador sem MANAGE_TENANT tenta alterar assinatura -> 403 Forbidden
        $client->request('POST', '/api/saas/subscription', [], [], $trainerHeaders, json_encode([
            'plan_code' => 'PRO',
        ]));
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // Treinador tenta ver uso do tenant -> 403 Forbidden
        $client->request('GET', '/api/saas/usage', [], [], $trainerHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
