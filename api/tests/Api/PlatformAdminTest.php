<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\PlatformAdminService;
use App\Application\Service\TokenService;
use App\Domain\Entity\PlatformAuditLog;
use App\Domain\Entity\SaaSPlan;
use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\User;
use App\Domain\Enum\MembershipStatus;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use App\Domain\Enum\UserStatus;
use App\Infrastructure\Repository\PlatformAuditLogRepository;
use App\Infrastructure\Repository\SaaSPlanRepository;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TenantRepository;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlatformAdminTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private TokenService $tokenService;
    private TenantRepository $tenantRepository;
    private UserRepository $userRepository;
    private TenantMembershipRepository $membershipRepository;
    private SaaSPlanRepository $planRepository;
    private StudentProfileRepository $studentRepository;
    private PlatformAuditLogRepository $auditLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    /**
     * @return array<string, mixed>
     */
    private function setupScenario(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->tokenService = $container->get(TokenService::class);
        $this->tenantRepository = $container->get(TenantRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->membershipRepository = $container->get(TenantMembershipRepository::class);
        $this->planRepository = $container->get(SaaSPlanRepository::class);
        $this->studentRepository = $container->get(StudentProfileRepository::class);
        $this->auditLogRepository = $container->get(PlatformAuditLogRepository::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Usuário normal (Personal Trainer)
        $normalUser = new User(
            'personal@gym.com',
            password_hash('password123', PASSWORD_BCRYPT),
            'Personal Trainer',
            null,
            null,
            UserStatus::ACTIVE,
            false // Não é platform admin
        );
        $this->userRepository->save($normalUser);

        // Usuário Platform Admin
        $adminUser = new User(
            'admin@saasplatform.com',
            password_hash('admin12345', PASSWORD_BCRYPT),
            'Super Administrator',
            null,
            null,
            UserStatus::ACTIVE,
            true // É platform admin!
        );
        $this->userRepository->save($adminUser);

        // Tenant Alpha
        $tenantAlpha = new Tenant('Alpha Academy', TenantType::ASSESSORIA, null, '12.345.678/0001-90');
        $this->tenantRepository->save($tenantAlpha);

        $membershipNormal = new TenantMembership($normalUser, $tenantAlpha, TenantRole::OWNER);
        $this->membershipRepository->save($membershipNormal);

        // Aluno no Tenant Alpha
        $studentUser = new User('student@gym.com', password_hash('pass', PASSWORD_BCRYPT), 'Student One');
        $this->userRepository->save($studentUser);
        $studentProfile = new StudentProfile($studentUser, $tenantAlpha);
        $this->studentRepository->save($studentProfile);

        // Planos SaaS iniciais
        $starterPlan = new SaaSPlan('STARTER', 'Starter Plan', 49.90, 499.00, 15, 1, 'Para personais iniciando');
        $proPlan = new SaaSPlan('PRO', 'Pro Plan', 149.90, 1499.00, 100, 5, 'Para assessorias em crescimento');
        $this->planRepository->save($starterPlan);
        $this->planRepository->save($proPlan);

        return [
            'client' => $client,
            'normalUser' => $normalUser,
            'adminUser' => $adminUser,
            'tenantAlpha' => $tenantAlpha,
            'membershipNormal' => $membershipNormal,
            'starterPlan' => $starterPlan,
            'proPlan' => $proPlan,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getAuthHeaders(User $user, ?Tenant $tenant = null, ?TenantMembership $membership = null): array
    {
        $token = $this->tokenService->createAccessToken($user, $tenant, $membership);
        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ];
        if ($tenant !== null) {
            $headers['HTTP_X_TENANT_ID'] = $tenant->getId();
        }
        return $headers;
    }

    public function testNonAdminCannotAccessPlatformAdminEndpoints(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $normalHeaders = $this->getAuthHeaders($data['normalUser'], $data['tenantAlpha'], $data['membershipNormal']);

        $client->request('GET', '/api/admin/metrics', [], [], $normalHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/admin/tenants', [], [], $normalHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/admin/audit-logs', [], [], $normalHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testPlatformAdminCanViewMetrics(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);

        $client->request('GET', '/api/admin/metrics', [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('tenants', $res);
        $this->assertSame(1, $res['tenants']['total']);
        $this->assertSame(1, $res['tenants']['active']);
        $this->assertSame(0, $res['tenants']['suspended']);
        $this->assertArrayHasKey('users', $res);
        $this->assertSame(3, $res['users']['total_active']); // normalUser, adminUser, studentUser
        $this->assertArrayHasKey('students', $res);
        $this->assertSame(1, $res['students']['total_active']);
    }

    public function testPlatformAdminCanListAndFilterTenants(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);

        $client->request('GET', '/api/admin/tenants', [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $res['total']);
        $this->assertSame('Alpha Academy', $res['items'][0]['name']);
        $this->assertSame(1, $res['items'][0]['active_students']);

        // Busca inexistente
        $client->request('GET', '/api/admin/tenants?search=Inexistente', [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resNotFound = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(0, $resNotFound['total']);
    }

    public function testPlatformAdminCanGetTenantDetail(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);
        $tenantId = $data['tenantAlpha']->getId();

        $client->request('GET', '/api/admin/tenants/' . $tenantId, [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame($tenantId, $res['tenant']['id']);
        $this->assertSame('Alpha Academy', $res['tenant']['name']);
        $this->assertCount(1, $res['members']);
        $this->assertSame('Personal Trainer', $res['members'][0]['name']);
    }

    public function testPlatformAdminCanSuspendAndReactivateTenantWithAudit(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);
        $tenantId = $data['tenantAlpha']->getId();

        // 1. Suspender tenant
        $client->request('PATCH', '/api/admin/tenants/' . $tenantId . '/status', [], [], $adminHeaders, json_encode([
            'status' => 'SUSPENDED',
            'reason' => 'Inadimplência recorrente de pagamento do SaaS',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('SUSPENDED', $res['status']);

        // 2. Verificar que o usuário normal é bloqueado
        $normalHeaders = $this->getAuthHeaders($data['normalUser'], $data['tenantAlpha'], $data['membershipNormal']);
        $client->request('GET', '/api/students', [], [], $normalHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // 3. Reativar tenant
        $client->request('PATCH', '/api/admin/tenants/' . $tenantId . '/status', [], [], $adminHeaders, json_encode([
            'status' => 'ACTIVE',
            'reason' => 'Pagamento regularizado pelo cliente',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 4. Usuário normal volta a ter acesso
        $client->request('GET', '/api/students', [], [], $normalHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 5. Verificar logs de auditoria gravados
        $logs = $this->auditLogRepository->findByFilters();
        $this->assertGreaterThanOrEqual(2, count($logs));
        $this->assertSame('TENANT_STATUS_UPDATED', $logs[0]->getAction());
        $this->assertSame('TENANT_SUSPENDED', $logs[1]->getAction());
    }

    public function testPlatformAdminCanCreateAndEditSaaSPlan(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);

        // 1. Criar novo plano
        $client->request('POST', '/api/admin/saas/plans', [], [], $adminHeaders, json_encode([
            'code' => 'ENTERPRISE',
            'name' => 'Enterprise Mega',
            'description' => 'Redes de academias e grandes assessorias',
            'price_monthly' => 399.00,
            'price_yearly' => 3990.00,
            'max_students' => 1000,
            'max_trainers' => 50,
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $resCreated = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('ENTERPRISE', $resCreated['code']);
        $planId = $resCreated['id'];

        // 2. Editar plano existente
        $client->request('PUT', '/api/admin/saas/plans/' . $planId, [], [], $adminHeaders, json_encode([
            'name' => 'Enterprise Mega Ultimate',
            'price_monthly' => 449.00,
            'max_students' => 1500,
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resUpdated = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Enterprise Mega Ultimate', $resUpdated['name']);
        $this->assertEquals(449.00, $resUpdated['price_monthly']);
        $this->assertSame(1500, $resUpdated['max_students']);
    }

    public function testPlatformAdminCanOverrideTenantSubscription(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);
        $tenantId = $data['tenantAlpha']->getId();

        // Atribuir plano PRO para o Tenant Alpha
        $client->request('PUT', '/api/admin/tenants/' . $tenantId . '/subscription', [], [], $adminHeaders, json_encode([
            'plan_code' => 'PRO',
            'reason' => 'Upgrade promocional concedido pelo time de vendas',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('ACTIVE', $res['status']);
        $this->assertSame('PRO', $res['plan']['code']);

        // Consultar detalhes do tenant para confirmar assinatura
        $client->request('GET', '/api/admin/tenants/' . $tenantId, [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resDetail = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertNotNull($resDetail['subscription']);
        $this->assertSame('PRO', $resDetail['subscription']['plan_code']);
    }

    public function testPlatformAdminCanListAuditLogs(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $adminHeaders = $this->getAuthHeaders($data['adminUser']);

        // Gera um log alterando o plano
        $client->request('POST', '/api/admin/saas/plans', [], [], $adminHeaders, json_encode([
            'code' => 'TRIAL_VIP',
            'name' => 'Trial VIP',
            'price_monthly' => 0.0,
            'price_yearly' => 0.0,
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // Consulta os logs de auditoria
        $client->request('GET', '/api/admin/audit-logs', [], [], $adminHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(1, $res['count']);
        $this->assertSame('Super Administrator', $res['logs'][0]['admin_name']);
        $this->assertSame('SAAS_PLAN_CREATED', $res['logs'][0]['action']);
    }
}
