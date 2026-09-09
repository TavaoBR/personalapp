<?php

declare(strict_types=1);

namespace App\Tests\Api;

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

final class FinancialTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private TokenService $tokenService;
    private TenantIdentityService $identityService;

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

        // Reset schema
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 1. Tenant Alpha
        $alphaData = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'pass123'
        );
        $tenantAlpha = $alphaData['tenant'];
        $ownerUser = $alphaData['user'];

        // Treinador Alpha (sem permissão VIEW_FINANCES)
        $trainerUser = new User('trainer@alpha.com', 'pass123', 'Treinador Alpha');
        $this->entityManager->persist($trainerUser);
        $membershipTrainer = new TenantMembership($trainerUser, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipTrainer);
        $trainerProfile = new TrainerProfile($trainerUser, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile);

        // Aluno Alpha
        $studentProfile = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha',
            'student@alpha.com',
            'pass123'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile, $studentProfile);
        $studentUser = $studentProfile->getUser();
        $membershipStudent = $this->entityManager->getRepository(TenantMembership::class)->findOneBy([
            'user' => $studentUser,
            'tenant' => $tenantAlpha,
        ]);

        // 2. Tenant Beta (Isolamento)
        $betaData = $this->identityService->createTenantWithOwner(
            'Assessoria Beta',
            TenantType::ASSESSORIA,
            'Owner Beta',
            'owner@beta.com',
            'pass123'
        );
        $tenantBeta = $betaData['tenant'];

        $this->entityManager->flush();

        return [
            'client' => $client,
            'tenantAlpha' => $tenantAlpha,
            'tenantBeta' => $tenantBeta,
            'ownerUser' => $ownerUser,
            'membershipOwner' => $alphaData['membership'],
            'trainerUser' => $trainerUser,
            'membershipTrainer' => $membershipTrainer,
            'studentUser' => $studentUser,
            'membershipStudent' => $membershipStudent,
            'studentProfile' => $studentProfile,
            'ownerBeta' => $betaData['user'],
            'membershipOwnerBeta' => $betaData['membership'],
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

    public function testCreateCoachingPlanAndEnrollStudent(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $studentId = $data['studentProfile']->getId();

        // 1. Criar Coaching Plan (Consultoria Mensal R$ 250,00)
        $client->request('POST', '/api/finances/coaching-plans', [], [], $ownerHeaders, json_encode([
            'name' => 'Consultoria Online Mensal',
            'price' => 250.0,
            'billing_period' => 'MONTHLY',
            'duration_months' => 1,
            'description' => 'Acompanhamento completo de treinos e dieta',
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $planRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Consultoria Online Mensal', $planRes['name']);
        $this->assertEquals(250.0, $planRes['price']);
        $planId = $planRes['id'];

        // 2. Matricular o aluno no plano com geração da primeira fatura
        $client->request('POST', "/api/students/{$studentId}/enrollments", [], [], $ownerHeaders, json_encode([
            'coaching_plan_id' => $planId,
            'billing_day' => 10,
            'generate_first_payment' => true,
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $enrollRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Consultoria Online Mensal', $enrollRes['plan_name']);
        $this->assertEquals(250.0, $enrollRes['price']);
        $this->assertSame('ACTIVE', $enrollRes['status']);

        // 3. Verifica pagamentos gerados para o aluno
        $client->request('GET', "/api/students/{$studentId}/payments", [], [], $ownerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $paymentsRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $paymentsRes['payments']);
        $this->assertEquals(250.0, $paymentsRes['payments'][0]['amount']);
        $this->assertSame('PENDING', $paymentsRes['payments'][0]['status']);
    }

    public function testPayPaymentAndDashboardMetrics(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria cobrança avulsa 1 (vencimento hoje, R$ 200,00)
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $client->request('POST', '/api/finances/payments', [], [], $ownerHeaders, json_encode([
            'student_id' => $studentId,
            'amount' => 200.0,
            'due_date' => $today,
            'notes' => 'Avaliação física avulsa',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $p1 = json_decode((string) $client->getResponse()->getContent(), true);
        $payment1Id = $p1['id'];

        // 2. Dá baixa (marca como pago com PIX)
        $client->request('POST', "/api/finances/payments/{$payment1Id}/pay", [], [], $ownerHeaders, json_encode([
            'payment_method' => 'PIX',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $paidRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('PAID', $paidRes['status']);
        $this->assertSame('PIX', $paidRes['payment_method']);
        $this->assertNotNull($paidRes['paid_at']);

        // 3. Cria cobrança em atraso (vencida há 5 dias, R$ 150,00)
        $pastDate = (new \DateTimeImmutable('-5 days'))->format('Y-m-d');
        $client->request('POST', '/api/finances/payments', [], [], $ownerHeaders, json_encode([
            'student_id' => $studentId,
            'amount' => 150.0,
            'due_date' => $pastDate,
            'notes' => 'Mensalidade atrasada',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 4. Consulta Dashboard Financeiro
        $client->request('GET', '/api/finances/dashboard', [], [], $ownerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $dash = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertEquals(200.0, $dash['total_revenue_month']);
        $this->assertEquals(150.0, $dash['overdue_revenue']);
        $this->assertCount(1, $dash['overdue_payments']);
        $this->assertEquals(150.0, $dash['overdue_payments'][0]['amount']);
    }

    public function testStudentMePayments(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria cobrança
        $client->request('POST', '/api/finances/payments', [], [], $ownerHeaders, json_encode([
            'student_id' => $studentId,
            'amount' => 180.0,
            'due_date' => '2026-03-20',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Aluno consulta /api/me/payments
        $client->request('GET', '/api/me/payments', [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $res['payments']);
        $this->assertEquals(180.0, $res['payments'][0]['amount']);
    }

    public function testSecurityAndTenantIsolationAntiEnumeration(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $ownerHeaders = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $betaOwnerHeaders = $this->getAuthHeaders($data['ownerBeta'], $data['tenantBeta'], $data['membershipOwnerBeta']);

        // 1. Owner cria plano
        $client->request('POST', '/api/finances/coaching-plans', [], [], $ownerHeaders, json_encode([
            'name' => 'Plano Alpha Confidencial',
            'price' => 500.0,
        ]));
        $plan = json_decode((string) $client->getResponse()->getContent(), true);
        $planId = $plan['id'];

        // 2. Treinador de Alpha (sem permissão VIEW_FINANCES) tenta ver dashboard -> 403 Forbidden
        $client->request('GET', '/api/finances/dashboard', [], [], $trainerHeaders);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // 3. Treinador de Alpha tenta criar plano -> 403 Forbidden
        $client->request('POST', '/api/finances/coaching-plans', [], [], $trainerHeaders, json_encode([
            'name' => 'Plano Ilegal',
            'price' => 10.0,
        ]));
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // 4. Owner de Beta tenta ver plano de Alpha -> 404 Not Found (anti-enumeração)
        $client->request('GET', "/api/finances/coaching-plans/{$planId}", [], [], $betaOwnerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
