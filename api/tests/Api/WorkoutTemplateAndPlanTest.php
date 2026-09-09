<?php

declare(strict_types=1);

namespace App\Tests\Api;

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

final class WorkoutTemplateAndPlanTest extends WebTestCase
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

        // 1. Cria Exercícios Globais
        $globalBench = new GlobalExercise(
            name: 'Supino Reto',
            muscleGroup: MuscleGroup::CHEST,
            equipment: 'Barra'
        );
        $this->entityManager->persist($globalBench);

        $globalSquat = new GlobalExercise(
            name: 'Agachamento',
            muscleGroup: MuscleGroup::LEGS,
            equipment: 'Rack'
        );
        $this->entityManager->persist($globalSquat);

        // Gera versão 1 para os globais
        $vBench = new \App\Domain\Entity\ExerciseVersion('Supino Reto', MuscleGroup::CHEST, 1, null, $globalBench);
        $this->entityManager->persist($vBench);
        $vSquat = new \App\Domain\Entity\ExerciseVersion('Agachamento', MuscleGroup::LEGS, 1, null, $globalSquat);
        $this->entityManager->persist($vSquat);

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

        // Treinador 1 no Tenant Alpha
        $userTrainer1 = new User('trainer1@alpha.com', 'hash', 'Trainer One');
        $this->entityManager->persist($userTrainer1);
        $memTrainer1 = new TenantMembership($userTrainer1, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($memTrainer1);
        $trainerProfile1 = new TrainerProfile($userTrainer1, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile1);

        // Treinador 2 no Tenant Alpha
        $userTrainer2 = new User('trainer2@alpha.com', 'hash', 'Trainer Two');
        $this->entityManager->persist($userTrainer2);
        $memTrainer2 = new TenantMembership($userTrainer2, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($memTrainer2);
        $trainerProfile2 = new TrainerProfile($userTrainer2, $tenantAlpha, null, 'CREF-002');
        $this->entityManager->persist($trainerProfile2);

        // Aluno 1 no Tenant Alpha (atribuído ao Treinador 1)
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

        // Aluno 2 no Tenant Alpha (atribuído ao Treinador 2)
        $studentProfile2 = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha Dois',
            'student2@alpha.com',
            'hash'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile2, $studentProfile2);
        $memStudent2 = $this->entityManager->getRepository(TenantMembership::class)->findOneBy([
            'user' => $studentProfile2->getUser(),
            'tenant' => $tenantAlpha,
        ]);

        // 3. Tenant Beta (Outro Personal independente)
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
            'global_bench' => $globalBench,
            'global_squat' => $globalSquat,
            'v_bench' => $vBench,
            'v_squat' => $vSquat,
            'tenant_alpha' => $tenantAlpha,
            'owner_alpha' => $ownerAlpha,
            'membership_owner_alpha' => $ownerAlphaMembership,
            'trainer1' => $userTrainer1,
            'membership_trainer1' => $memTrainer1,
            'trainer2' => $userTrainer2,
            'membership_trainer2' => $memTrainer2,
            'student1' => $studentProfile1,
            'membership_student1' => $memStudent1,
            'student2' => $studentProfile2,
            'membership_student2' => $memStudent2,
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

    public function testCreateWorkoutTemplateWithVersionAndDaysAndExercises(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        $payload = [
            'name' => 'Template Hipertrofia AB',
            'description' => 'Rotina de 4 dias por semana',
            'objective' => 'Hipertrofia',
            'level' => 'Intermediário',
            'days' => [
                [
                    'name' => 'Treino A - Peito',
                    'order_index' => 0,
                    'exercises' => [
                        [
                            'exercise_version_id' => $ctx['v_bench']->getId(),
                            'order_index' => 0,
                            'sets' => 4,
                            'reps' => '8-10',
                            'weight' => '30kg',
                            'rest_seconds' => 90,
                            'rpe' => '8',
                        ],
                    ],
                ],
                [
                    'name' => 'Treino B - Pernas',
                    'order_index' => 1,
                    'exercises' => [
                        [
                            'exercise_version_id' => $ctx['v_squat']->getId(),
                            'order_index' => 0,
                            'sets' => 4,
                            'reps' => '10-12',
                            'weight' => '50kg',
                            'rest_seconds' => 120,
                            'rpe' => '9',
                        ],
                    ],
                ],
            ],
        ];

        $client->request('POST', '/api/workout-templates', [], [], $headers, json_encode($payload));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Template Hipertrofia AB', $data['name']);
        $this->assertSame(1, $data['current_version']['version_number']);
        $this->assertCount(2, $data['current_version']['days']);
        $this->assertSame('Treino A - Peito', $data['current_version']['days'][0]['name']);
        $this->assertCount(1, $data['current_version']['days'][0]['exercises']);
        $this->assertSame('Supino Reto', $data['current_version']['days'][0]['exercises'][0]['exercise_name']);
    }

    public function testVersionWorkoutTemplatePreservesHistory(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        // 1. Cria v1
        $client->request('POST', '/api/workout-templates', [], [], $headers, json_encode([
            'name' => 'Template Base',
            'days' => [
                [
                    'name' => 'Treino A',
                    'exercises' => [
                        ['exercise_version_id' => $ctx['v_bench']->getId(), 'sets' => 3, 'reps' => '10'],
                    ],
                ],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $created = json_decode($client->getResponse()->getContent(), true);
        $templateId = $created['id'];

        // 2. Cria v2 com novas séries
        $client->request('POST', "/api/workout-templates/{$templateId}/versions", [], [], $headers, json_encode([
            'objective' => 'Força Máxima',
            'days' => [
                [
                    'name' => 'Treino A - Modificado',
                    'exercises' => [
                        ['exercise_version_id' => $ctx['v_bench']->getId(), 'sets' => 5, 'reps' => '5'],
                    ],
                ],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $v2Data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, $v2Data['version_number']);

        // 3. Lista histórico de versões
        $client->request('GET', "/api/workout-templates/{$templateId}/versions", [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $hist = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $hist['versions']);
        $this->assertSame(2, $hist['versions'][0]['version_number']);
        $this->assertSame(1, $hist['versions'][1]['version_number']);
    }

    public function testAssignTemplateToStudentCreatesIndependentPlan(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);

        // 1. Cria Template
        $client->request('POST', '/api/workout-templates', [], [], $headers, json_encode([
            'name' => 'Template Origem',
            'days' => [
                [
                    'name' => 'Dia 1',
                    'exercises' => [
                        ['exercise_version_id' => $ctx['v_bench']->getId(), 'sets' => 3, 'reps' => '12'],
                    ],
                ],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $template = json_decode($client->getResponse()->getContent(), true);
        $templateId = $template['id'];

        // 2. Atribui ao Aluno 1
        $student1Id = $ctx['student1']->getId();
        $client->request('POST', "/api/workout-templates/{$templateId}/assign", [], [], $headers, json_encode([
            'student_ids' => [$student1Id],
            'plan_name' => 'Ficha Personalizada Aluno 1',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $assigned = json_decode($client->getResponse()->getContent(), true);
        $planId = $assigned['assigned'][0]['plan_id'];

        // 3. Verifica a ficha criada para o aluno
        $client->request('GET', "/api/workout-plans/{$planId}", [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $planDetail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Ficha Personalizada Aluno 1', $planDetail['name']);
        $this->assertSame($templateId, $planDetail['source_template_id']);
        $this->assertSame(3, $planDetail['current_version']['days'][0]['exercises'][0]['sets']);

        // 4. Treinador altera a ficha do aluno gerando versão 2 individual da ficha
        $client->request('POST', "/api/workout-plans/{$planId}/versions", [], [], $headers, json_encode([
            'notes' => 'Aumentando carga e diminuindo repetições',
            'days' => [
                [
                    'name' => 'Dia 1 Customizado',
                    'exercises' => [
                        ['exercise_version_id' => $ctx['v_bench']->getId(), 'sets' => 5, 'reps' => '6'],
                    ],
                ],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 5. Verifica que o template original NÃO foi modificado (permanece 3 sets)
        $client->request('GET', "/api/workout-templates/{$templateId}", [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $freshTemplate = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $freshTemplate['current_version']['days'][0]['exercises'][0]['sets']);
    }

    public function testStudentSelfServiceMeWorkouts(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];

        // 1. Treinador cria ficha para Aluno 1
        $headersTrainer = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);
        $client->request('POST', "/api/students/{$ctx['student1']->getId()}/workout-plans", [], [], $headersTrainer, json_encode([
            'name' => 'Treino Semanal Aluno 1',
            'days' => [
                [
                    'name' => 'Peito e Braços',
                    'exercises' => [
                        ['exercise_version_id' => $ctx['v_bench']->getId(), 'sets' => 4, 'reps' => '10'],
                    ],
                ],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Aluno 1 acessa /api/me/workouts
        $headersStudent = $this->authHeaders($ctx['student1']->getUser(), $ctx['tenant_alpha'], $ctx['membership_student1']);
        $client->request('GET', '/api/me/workouts', [], [], $headersStudent);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($data['plan']);
        $this->assertSame('Treino Semanal Aluno 1', $data['plan']['name']);
        $this->assertSame('Peito e Braços', $data['plan']['current_version']['days'][0]['name']);
    }

    public function testCrossTenantAndCrossStudentIsolationReturns404(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];

        // 1. Cria ficha para Aluno 1 no Tenant Alpha
        $headersTrainer1 = $this->authHeaders($ctx['trainer1'], $ctx['tenant_alpha'], $ctx['membership_trainer1']);
        $client->request('POST', "/api/students/{$ctx['student1']->getId()}/workout-plans", [], [], $headersTrainer1, json_encode([
            'name' => 'Ficha Aluno 1',
            'days' => [],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $plan = json_decode($client->getResponse()->getContent(), true);
        $planId = $plan['id'];

        // 2. Treinador 2 (não atribuído ao Aluno 1) tenta acessar a ficha -> 404 (sem leak)
        $headersTrainer2 = $this->authHeaders($ctx['trainer2'], $ctx['tenant_alpha'], $ctx['membership_trainer2']);
        $client->request('GET', "/api/workout-plans/{$planId}", [], [], $headersTrainer2);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 3. Usuário do Tenant Beta tenta acessar a ficha de Alpha -> 404
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('GET', "/api/workout-plans/{$planId}", [], [], $headersBeta);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
