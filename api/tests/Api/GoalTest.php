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

final class GoalTest extends WebTestCase
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

        // Global metric definition
        $metricWeight = new MetricDefinition('Peso Corporal', 'WEIGHT', 'kg', 'BODY_COMPOSITION');
        $this->entityManager->persist($metricWeight);

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

        $trainerUser = new User('trainer@alpha.com', 'pass123', 'Treinador Alpha');
        $this->entityManager->persist($trainerUser);
        $membershipTrainer = new TenantMembership($trainerUser, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipTrainer);
        $trainerProfile = new TrainerProfile($trainerUser, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile);

        // Unassigned trainer in Alpha
        $otherTrainerUser = new User('othertrainer@alpha.com', 'pass123', 'Treinador Outro');
        $this->entityManager->persist($otherTrainerUser);
        $membershipOther = new TenantMembership($otherTrainerUser, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipOther);
        $otherTrainerProfile = new TrainerProfile($otherTrainerUser, $tenantAlpha, null, 'CREF-002');
        $this->entityManager->persist($otherTrainerProfile);

        // Student assigned to trainerProfile
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

        $betaTrainerUser = new User('trainer@beta.com', 'pass123', 'Treinador Beta');
        $this->entityManager->persist($betaTrainerUser);
        $membershipBetaTrainer = new TenantMembership($betaTrainerUser, $tenantBeta, TenantRole::TRAINER);
        $this->entityManager->persist($membershipBetaTrainer);
        $betaTrainerProfile = new TrainerProfile($betaTrainerUser, $tenantBeta, null, 'CREF-BETA');
        $this->entityManager->persist($betaTrainerProfile);

        $this->entityManager->flush();

        return [
            'client' => $client,
            'tenantAlpha' => $tenantAlpha,
            'tenantBeta' => $tenantBeta,
            'ownerUser' => $ownerUser,
            'membershipOwner' => $alphaData['membership'],
            'trainerUser' => $trainerUser,
            'membershipTrainer' => $membershipTrainer,
            'otherTrainerUser' => $otherTrainerUser,
            'membershipOtherTrainer' => $membershipOther,
            'studentUser' => $studentUser,
            'membershipStudent' => $membershipStudent,
            'studentProfile' => $studentProfile,
            'betaTrainerUser' => $betaTrainerUser,
            'membershipBetaTrainer' => $membershipBetaTrainer,
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

    public function testCreateGoalAndListGoals(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentId = $data['studentProfile']->getId();

        // 1. Criar meta para o aluno
        $client->request('POST', "/api/students/{$studentId}/goals", [], [], $trainerHeaders, json_encode([
            'title' => 'Redução de Peso para 75kg',
            'description' => 'Meta de emagrecimento pré-verão',
            'metric_code' => 'WEIGHT',
            'initial_value' => 85.0,
            'target_value' => 75.0,
            'unit' => 'kg',
            'start_date' => '2026-01-15',
            'target_date' => '2026-06-15',
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $res = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $res);
        $this->assertSame('Redução de Peso para 75kg', $res['title']);
        $this->assertEquals(85.0, $res['initial_value']);
        $this->assertEquals(75.0, $res['target_value']);
        $this->assertEquals(85.0, $res['current_value']);
        $this->assertEquals(0.0, $res['progress_percentage']);
        $this->assertSame('IN_PROGRESS', $res['status']);
        $this->assertCount(1, $res['progress_history']); // Registro inicial automático
        $goalId = $res['id'];

        // 2. Listar metas do aluno
        $client->request('GET', "/api/students/{$studentId}/goals", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $listRes = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(1, $listRes['goals']);
        $this->assertSame($goalId, $listRes['goals'][0]['id']);
    }

    public function testRecordProgressAndAutoAchieveGoal(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);
        $studentId = $data['studentProfile']->getId();

        // 1. Treinador cria meta (85kg -> 75kg)
        $client->request('POST', "/api/students/{$studentId}/goals", [], [], $trainerHeaders, json_encode([
            'title' => 'Meta de Peso',
            'metric_code' => 'WEIGHT',
            'initial_value' => 85.0,
            'target_value' => 75.0,
        ]));
        $goalRes = json_decode((string)$client->getResponse()->getContent(), true);
        $goalId = $goalRes['id'];

        // 2. Registra progresso 1: 80kg (50% concluído)
        $client->request('POST', "/api/goals/{$goalId}/progress", [], [], $trainerHeaders, json_encode([
            'value' => 80.0,
            'notes' => 'Primeiro mês de dieta consistente',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $p1 = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertEquals(80.0, $p1['value']);
        $this->assertEquals(85.0, $p1['previous_value']);
        $this->assertEquals(50.0, $p1['goal_progress_percentage']);
        $this->assertSame('IN_PROGRESS', $p1['goal_status']);

        // 3. Aluno registra progresso atingindo a meta: 74.5kg
        $client->request('POST', "/api/goals/{$goalId}/progress", [], [], $studentHeaders, json_encode([
            'value' => 74.5,
            'notes' => 'Meta superada na pesagem matinal!',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $p2 = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertEquals(74.5, $p2['value']);
        $this->assertEquals(80.0, $p2['previous_value']);
        $this->assertEquals(105.0, $p2['goal_progress_percentage']);
        $this->assertSame('ACHIEVED', $p2['goal_status']);

        // 4. Detalhe da meta mostra histórico completo e achieved_at preenchido
        $client->request('GET', "/api/goals/{$goalId}", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detail = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertSame('ACHIEVED', $detail['status']);
        $this->assertNotNull($detail['achieved_at']);
        $this->assertCount(3, $detail['progress_history']); // 85 inicial + 80 + 74.5
    }

    public function testStudentMeGoals(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria meta
        $client->request('POST', "/api/students/{$studentId}/goals", [], [], $trainerHeaders, json_encode([
            'title' => 'Frequência Semanal 4x',
            'initial_value' => 2.0,
            'target_value' => 4.0,
            'unit' => 'treinos/sem',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Aluno consulta /api/me/goals
        $client->request('GET', '/api/me/goals', [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(1, $res['goals']);
        $this->assertSame('Frequência Semanal 4x', $res['goals'][0]['title']);
    }

    public function testSecurityAndTenantIsolationAntiEnumeration(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $unassignedTrainerHeaders = $this->getAuthHeaders($data['otherTrainerUser'], $data['tenantAlpha'], $data['membershipOtherTrainer']);
        $betaTrainerHeaders = $this->getAuthHeaders($data['betaTrainerUser'], $data['tenantBeta'], $data['membershipBetaTrainer']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria meta em Alpha
        $client->request('POST', "/api/students/{$studentId}/goals", [], [], $trainerHeaders, json_encode([
            'title' => 'Meta Confidencial',
            'initial_value' => 10.0,
            'target_value' => 20.0,
        ]));
        $goalRes = json_decode((string)$client->getResponse()->getContent(), true);
        $goalId = $goalRes['id'];

        // 2. Treinador Beta tenta listar metas do aluno de Alpha -> 404
        $client->request('GET', "/api/students/{$studentId}/goals", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 3. Treinador Beta tenta acessar a meta diretamente -> 404
        $client->request('GET', "/api/goals/{$goalId}", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 4. Treinador não atribuído no mesmo tenant tenta listar metas do aluno -> 404
        $client->request('GET', "/api/students/{$studentId}/goals", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 5. Treinador não atribuído no mesmo tenant tenta ver detalhe da meta -> 404
        $client->request('GET', "/api/goals/{$goalId}", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
