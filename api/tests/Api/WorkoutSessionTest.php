<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\StudentWorkoutPlanService;
use App\Application\Service\TenantIdentityService;
use App\Application\Service\TokenService;
use App\Domain\Entity\GlobalExercise;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\MuscleGroup;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WorkoutSessionTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private TokenService $tokenService;
    private TenantIdentityService $identityService;
    private StudentWorkoutPlanService $planService;

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
        $this->planService = $container->get(StudentWorkoutPlanService::class);

        // Reset schema
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 1. Exercício Global
        $globalBench = new GlobalExercise('Supino Reto', MuscleGroup::CHEST, 'Barra');
        $this->entityManager->persist($globalBench);
        $vBench = new \App\Domain\Entity\ExerciseVersion('Supino Reto', MuscleGroup::CHEST, 1, null, $globalBench);
        $this->entityManager->persist($vBench);

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

        // Cria Ficha Ativa para Aluno 1
        $plan = $this->planService->createCustomPlan(
            tenant: $tenantAlpha,
            student: $studentProfile1,
            name: 'Ficha Hipertrofia Aluno 1',
            daysData: [
                [
                    'name' => 'Treino A',
                    'exercises' => [
                        [
                            'exercise_version_id' => $vBench->getId(),
                            'sets' => 2,
                            'reps' => '8-10',
                            'weight' => '40kg',
                            'rest_seconds' => 60,
                        ],
                    ],
                ],
            ]
        );

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
            'tenant_alpha' => $tenantAlpha,
            'owner_alpha' => $ownerAlpha,
            'membership_owner_alpha' => $ownerAlphaMembership,
            'trainer1' => $userTrainer1,
            'membership_trainer1' => $memTrainer1,
            'trainer2' => $userTrainer2,
            'membership_trainer2' => $memTrainer2,
            'student1' => $studentProfile1,
            'membership_student1' => $memStudent1,
            'plan1' => $plan,
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

    public function testStudentCanStartSessionAndRecordSetsWithRpeAndWeight(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);

        // 1. Aluno inicia sessão de treino
        $client->request('POST', '/api/workout-sessions', [], [], $headersStudent, json_encode([]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $session = json_decode($client->getResponse()->getContent(), true);

        $sessionId = $session['id'];
        $this->assertSame('IN_PROGRESS', $session['status']);
        $this->assertCount(1, $session['exercises']);
        $exerciseExecId = $session['exercises'][0]['id'];
        $this->assertCount(2, $session['exercises'][0]['sets']);

        // 2. Aluno registra série 1
        $client->request('POST', "/api/workout-sessions/{$sessionId}/exercises/{$exerciseExecId}/sets", [], [], $headersStudent, json_encode([
            'set_number' => 1,
            'reps_performed' => 10,
            'weight_performed' => 40.0,
            'rpe' => 8.0,
            'rest_seconds' => 60,
            'completed' => true,
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $set1 = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(10, $set1['reps_performed']);
        $this->assertEquals(40.0, $set1['weight_performed']);
        $this->assertEquals(8.0, $set1['rpe']);
        $this->assertTrue($set1['completed']);

        // 3. Aluno registra série 2
        $client->request('POST', "/api/workout-sessions/{$sessionId}/exercises/{$exerciseExecId}/sets", [], [], $headersStudent, json_encode([
            'set_number' => 2,
            'reps_performed' => 9,
            'weight_performed' => 42.5,
            'rpe' => 9.0,
            'rest_seconds' => 90,
            'completed' => true,
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 4. Aluno finaliza sessão de treino
        $client->request('POST', "/api/workout-sessions/{$sessionId}/complete", [], [], $headersStudent, json_encode([
            'feedback_notes' => 'Treino excelente, aumentei a carga na segunda série.',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $completed = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('COMPLETED', $completed['status']);
        $this->assertNotNull($completed['finished_at']);
        $this->assertGreaterThanOrEqual(0, $completed['duration_seconds']);
        $this->assertEquals(100.0, $completed['completion_percentage']);
        $this->assertSame('Treino excelente, aumentei a carga na segunda série.', $completed['feedback_notes']);
    }

    public function testTrainerCanViewStudentWorkoutHistoryWithPeriodFilters(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        // Aluno inicia e finaliza uma sessão
        $client->request('POST', '/api/workout-sessions', [], [], $headersStudent, json_encode([]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $session = json_decode($client->getResponse()->getContent(), true);
        $sessionId = $session['id'];

        $client->request('POST', "/api/workout-sessions/{$sessionId}/complete", [], [], $headersStudent, json_encode([]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Treinador consulta histórico do aluno
        $studentId = $ctx['student1']->getId();
        $client->request('GET', "/api/students/{$studentId}/workout-sessions?period=today", [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['history']);
        $this->assertSame('COMPLETED', $data['history'][0]['status']);
    }

    public function testStudentSelfServiceMeWorkoutHistory(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);

        // Aluno inicia e finaliza sessão
        $client->request('POST', '/api/workout-sessions', [], [], $headersStudent, json_encode([]));
        $sessionId = json_decode($client->getResponse()->getContent(), true)['id'];
        $client->request('POST', "/api/workout-sessions/{$sessionId}/complete", [], [], $headersStudent, json_encode([]));

        // Aluno consulta /api/me/workout-history
        $client->request('GET', '/api/me/workout-history', [], [], $headersStudent);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['history']);
    }

    public function testCrossTenantAndCrossStudentSessionIsolationReturns404(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);

        // Aluno 1 inicia sessão
        $client->request('POST', '/api/workout-sessions', [], [], $headersStudent, json_encode([]));
        $sessionId = json_decode($client->getResponse()->getContent(), true)['id'];

        // Treinador 2 (não atribuído a Aluno 1) tenta acessar sessão -> 404
        $headersTrainer2 = $this->authHeaders($ctx['trainer2'], $ctx['tenant_alpha'], $ctx['membership_trainer2']);
        $client->request('GET', "/api/workout-sessions/{$sessionId}", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Tenant Beta tenta acessar sessão de Alpha -> 404
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('GET', "/api/workout-sessions/{$sessionId}", [], [], $headersBeta);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
