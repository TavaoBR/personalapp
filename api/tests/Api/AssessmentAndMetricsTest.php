<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\TenantIdentityService;
use App\Application\Service\TokenService;
use App\Domain\Entity\MetricDefinition;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssessmentAndMetricsTest extends WebTestCase
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

        // 1. Cria Métricas Globais Padrão
        $metricWeight = new MetricDefinition('Peso Corporal', 'WEIGHT', 'kg', 'BODY_COMPOSITION');
        $this->entityManager->persist($metricWeight);

        $metricFat = new MetricDefinition('Percentual de Gordura', 'BODY_FAT', '%', 'BODY_COMPOSITION');
        $this->entityManager->persist($metricFat);

        $metricWaist = new MetricDefinition('Circunferência Abdominal', 'WAIST', 'cm', 'CIRCUMFERENCE');
        $this->entityManager->persist($metricWaist);

        // 2. Tenant Alpha (Assessoria)
        $alpha = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'hash'
        );
        $tenantAlpha = $alpha['tenant'];
        $ownerAlpha = $alpha['user'];
        $ownerAlphaMembership = $alpha['membership'];

        // Treinador 1
        $userTrainer1 = new User('trainer1@alpha.com', 'hash', 'Trainer One');
        $this->entityManager->persist($userTrainer1);
        $memTrainer1 = new TenantMembership($userTrainer1, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($memTrainer1);
        $trainerProfile1 = new TrainerProfile($userTrainer1, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile1);

        // Treinador 2
        $userTrainer2 = new User('trainer2@alpha.com', 'hash', 'Trainer Two');
        $this->entityManager->persist($userTrainer2);
        $memTrainer2 = new TenantMembership($userTrainer2, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($memTrainer2);
        $trainerProfile2 = new TrainerProfile($userTrainer2, $tenantAlpha, null, 'CREF-002');
        $this->entityManager->persist($trainerProfile2);

        // Aluno 1 atribuído ao Treinador 1
        $studentProfile1 = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha Um',
            'student1@alpha.com',
            'hash'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile1, $studentProfile1);
        $memStudent1 = $this->entityManager->getRepository(TenantMembership::class)->findOneBy([
            'user' => $studentProfile1->getUser(),
            'tenant' => $tenantAlpha,
        ]);

        // 3. Tenant Beta (Outro Personal)
        $beta = $this->identityService->createTenantWithOwner(
            'Personal Beta',
            TenantType::PERSONAL,
            'Owner Beta',
            'owner@beta.com',
            'hash'
        );
        $tenantBeta = $beta['tenant'];
        $ownerBeta = $beta['user'];
        $ownerBetaMembership = $beta['membership'];

        $this->entityManager->flush();

        return [
            'client' => $client,
            'metric_weight' => $metricWeight,
            'metric_fat' => $metricFat,
            'metric_waist' => $metricWaist,
            'tenant_alpha' => $tenantAlpha,
            'owner_alpha' => $ownerAlpha,
            'membership_owner_alpha' => $ownerAlphaMembership,
            'trainer1' => $userTrainer1,
            'membership_trainer1' => $memTrainer1,
            'trainer2' => $userTrainer2,
            'membership_trainer2' => $memTrainer2,
            'student1' => $studentProfile1,
            'membership_student1' => $memStudent1,
            'tenant_beta' => $tenantBeta,
            'owner_beta' => $ownerBeta,
            'membership_owner_beta' => $ownerBetaMembership,
        ];
    }

    private function authHeaders(User $user, ?\App\Domain\Entity\Tenant $tenant = null, ?TenantMembership $membership = null): array
    {
        $token = $this->tokenService->createAccessToken($user, $tenant, $membership);
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $tenant?->getId() ?? '',
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    public function testListMetricDefinitionsAndCreateCustomMetric(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        // 1. Lista métricas disponíveis
        $client->request('GET', '/api/metrics/definitions', [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $codes = array_column($data['metrics'], 'code');
        $this->assertContains('WEIGHT', $codes);
        $this->assertContains('BODY_FAT', $codes);
        $this->assertContains('WAIST', $codes);

        // 2. Treinador cria métrica customizada
        $client->request('POST', '/api/metrics/definitions', [], [], $headersTrainer, json_encode([
            'name' => 'Dobra Cutânea Subescapular',
            'code' => 'SKINFOLD_SUBESCAPULAR',
            'unit' => 'mm',
            'category' => 'BODY_COMPOSITION',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('SKINFOLD_SUBESCAPULAR', $created['code']);
        $this->assertTrue($created['is_custom']);
    }

    public function testCreatePhysicalAssessmentAndCheckRecordFeeding(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);
        $studentId = $ctx['student1']->getId();

        // 1. Treinador cria avaliação física completa
        $client->request('POST', "/api/students/{$studentId}/assessments", [], [], $headersTrainer, json_encode([
            'date' => '2026-09-01',
            'notes' => 'Avaliação física inicial',
            'metrics' => [
                ['metric_code' => 'WEIGHT', 'value' => 80.0],
                ['metric_code' => 'BODY_FAT', 'value' => 18.5],
                ['metric_code' => 'WAIST', 'value' => 86.0],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $assessment = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $assessment);
        $this->assertCount(3, $assessment['metrics']);
        $assessmentId = $assessment['id'];

        // 2. Consulta detalhe da avaliação
        $client->request('GET', "/api/assessments/{$assessmentId}", [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Avaliação física inicial', $detail['notes']);

        // 3. Aluno registra nova pesagem avulsa 15 dias depois
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);
        $client->request('POST', "/api/students/{$studentId}/metrics", [], [], $headersStudent, json_encode([
            'metric_code' => 'WEIGHT',
            'value' => 78.5,
            'date' => '2026-09-15',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 4. Consulta evolução/progresso do peso
        $client->request('GET', "/api/students/{$studentId}/progress?metric=WEIGHT", [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $prog = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $prog['data_points']);
        $this->assertEquals(80.0, $prog['stats']['initial']);
        $this->assertEquals(78.5, $prog['stats']['latest']);
        $this->assertEquals(-1.5, $prog['stats']['absolute_change']);
        $this->assertEquals(-1.88, $prog['stats']['percentage_change']);
    }

    public function testStudentSelfServiceMeAssessmentsAndProgress(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);
        $studentId = $ctx['student1']->getId();

        // Treinador cria avaliação
        $client->request('POST', "/api/students/{$studentId}/assessments", [], [], $headersTrainer, json_encode([
            'date' => '2026-09-01',
            'metrics' => [
                ['metric_code' => 'WEIGHT', 'value' => 70.0],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // Aluno consulta /api/me/assessments
        $client->request('GET', '/api/me/assessments', [], [], $headersStudent);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['assessments']);

        // Aluno consulta /api/me/progress
        $client->request('GET', '/api/me/progress?metric=WEIGHT', [], [], $headersStudent);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $prog = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(70.0, $prog['stats']['initial']);
    }

    public function testCrossTenantAndCrossStudentIsolationReturns404(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersTrainer1 = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);
        $studentId = $ctx['student1']->getId();

        // Treinador 1 cria avaliação para Aluno 1
        $client->request('POST', "/api/students/{$studentId}/assessments", [], [], $headersTrainer1, json_encode([
            'metrics' => [
                ['metric_code' => 'WEIGHT', 'value' => 70.0],
            ],
        ]));
        $assessmentId = json_decode($client->getResponse()->getContent(), true)['id'];

        // Treinador 2 tenta acessar avaliação do Aluno 1 -> 404
        $headersTrainer2 = $this->authHeaders($ctx['trainer2'], $ctx['tenant_alpha'], $ctx['membership_trainer2']);
        $client->request('GET', "/api/assessments/{$assessmentId}", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Treinador 2 tenta acessar progresso do Aluno 1 -> 404
        $client->request('GET', "/api/students/{$studentId}/progress", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Tenant Beta tenta acessar avaliação -> 404
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('GET', "/api/assessments/{$assessmentId}", [], [], $headersBeta);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
