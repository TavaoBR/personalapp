<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Application\Service\StudentWorkoutPlanService;
use App\Application\Service\TenantIdentityService;
use App\Application\Service\TokenService;
use App\Application\Service\WorkoutSessionService;
use App\Domain\Entity\GlobalExercise;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\MuscleGroup;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AnalyticsTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private TokenService $tokenService;
    private TenantIdentityService $identityService;
    private StudentWorkoutPlanService $planService;
    private WorkoutSessionService $sessionService;

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
        $this->sessionService = $container->get(WorkoutSessionService::class);

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

        // Cria Ficha para Aluno 1
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
                            'reps' => '10',
                            'weight' => '50kg',
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
            'v_bench' => $vBench,
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

    public function testStudentAnalyticsWithPeriodComparison(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $student = $ctx['student1'];
        $tenant = $ctx['tenant_alpha'];

        // Sessão no Período A (Hoje)
        $sessionA = $this->sessionService->startSession($tenant, $student);
        $execA = $sessionA->getExerciseExecutions()->first();
        $this->sessionService->recordSetExecution(
            $tenant,
            $sessionA->getId(),
            $execA->getId(),
            1,
            repsPerformed: 10,
            weightPerformed: 60.0,
            completed: true
        );
        $this->sessionService->completeSession($tenant, $sessionA->getId());

        // Treinador consulta analytics com comparação de períodos
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $tenant, $ctx['membership_trainer1']);
        $now = new DateTimeImmutable();
        $startA = $now->modify('-1 day')->format('Y-m-d');
        $endA = $now->modify('+1 day')->format('Y-m-d');
        $startB = $now->modify('-30 days')->format('Y-m-d');
        $endB = $now->modify('-20 days')->format('Y-m-d');

        $client->request('GET', "/api/students/{$student->getId()}/analytics?start_a={$startA}&end_a={$endA}&start_b={$startB}&end_b={$endB}", [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['period_a']['total_sessions']);
        $this->assertEquals(600.0, $data['period_a']['total_volume_kg']); // 10 reps * 60 kg = 600 kg
        $this->assertNotNull($data['comparison']);
        $this->assertSame(0, $data['comparison']['period_b']['total_sessions']);
        $this->assertSame(1, $data['comparison']['diff_sessions']);
        $this->assertEquals(600.0, $data['comparison']['diff_volume']);
    }

    public function testStudentPersonalRecordsPrs(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $student = $ctx['student1'];
        $tenant = $ctx['tenant_alpha'];

        // Sessão 1: Supino com 60kg
        $session1 = $this->sessionService->startSession($tenant, $student);
        $exec1 = $session1->getExerciseExecutions()->first();
        $this->sessionService->recordSetExecution(
            $tenant,
            $session1->getId(),
            $exec1->getId(),
            1,
            repsPerformed: 10,
            weightPerformed: 60.0,
            completed: true
        );
        $this->sessionService->completeSession($tenant, $session1->getId());

        // Sessão 2: Supino com 75kg (novo PR)
        $session2 = $this->sessionService->startSession($tenant, $student);
        $exec2 = $session2->getExerciseExecutions()->first();
        $this->sessionService->recordSetExecution(
            $tenant,
            $session2->getId(),
            $exec2->getId(),
            1,
            repsPerformed: 8,
            weightPerformed: 75.0,
            completed: true
        );
        $this->sessionService->completeSession($tenant, $session2->getId());

        // Aluno consulta /api/me/prs
        $headersStudent = $this->authHeaders($student->getUser(), $tenant, $ctx['membership_student1']);
        $client->request('GET', '/api/me/prs', [], [], $headersStudent);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['personal_records']);
        $this->assertSame('Supino Reto', $data['personal_records'][0]['exercise_name']);
        $this->assertEquals(75.0, $data['personal_records'][0]['max_weight']);
        $this->assertSame(8, $data['personal_records'][0]['reps_at_max']);
    }

    public function testPortfolioDashboard(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        $client->request('GET', '/api/analytics/dashboard', [], [], $headersTrainer);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('dashboard', $data);
        $this->assertSame(1, $data['dashboard']['total_students']);
        $this->assertSame(1, $data['dashboard']['active_students']);
        $this->assertSame(0, $data['dashboard']['students_without_active_plan']);
    }

    public function testCrossTenantAndCrossStudentAnalyticsIsolationReturns404(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $studentId = $ctx['student1']->getId();

        // Treinador 2 tenta acessar analytics do Aluno 1 -> 404
        $headersTrainer2 = $this->authHeaders($ctx['trainer2'], $ctx['tenant_alpha'], $ctx['membership_trainer2']);
        $client->request('GET', "/api/students/{$studentId}/analytics", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Treinador 2 tenta acessar PRs do Aluno 1 -> 404
        $client->request('GET', "/api/students/{$studentId}/prs", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Tenant Beta tenta acessar analytics do Aluno 1 de Alpha -> 404
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('GET', "/api/students/{$studentId}/analytics", [], [], $headersBeta);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
