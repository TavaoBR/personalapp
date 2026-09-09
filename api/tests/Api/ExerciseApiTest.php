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

final class ExerciseApiTest extends WebTestCase
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
            name: 'Supino Reto com Barra',
            muscleGroup: MuscleGroup::CHEST,
            equipment: 'Barra Olímpica e Banco Reto',
            description: 'Deitado no banco, empurre a barra para cima.'
        );
        $this->entityManager->persist($globalBench);

        $globalSquat = new GlobalExercise(
            name: 'Agachamento Livre',
            muscleGroup: MuscleGroup::LEGS,
            equipment: 'Barra e Rack',
            description: 'Agachamento até 90 graus.'
        );
        $this->entityManager->persist($globalSquat);

        // 2. Tenant Alpha (Assessoria)
        $alpha = $this->identityService->createTenantWithOwner(
            'Academia Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'hash'
        );
        $tenantAlpha = $alpha['tenant'];
        $ownerAlpha = $alpha['user'];
        $ownerAlphaMembership = $alpha['membership'];

        // Treinador Alpha
        $userTrainerAlpha = new User('trainer@alpha.com', 'hash', 'Trainer Alpha');
        $this->entityManager->persist($userTrainerAlpha);
        $memTrainerAlpha = new TenantMembership($userTrainerAlpha, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($memTrainerAlpha);
        $trainerProfileAlpha = new TrainerProfile($userTrainerAlpha, $tenantAlpha);
        $this->entityManager->persist($trainerProfileAlpha);

        // Aluno Alpha
        $studentAlpha = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha',
            'student@alpha.com',
            'hash'
        );
        $memStudentAlpha = $this->entityManager->getRepository(TenantMembership::class)->findOneBy([
            'user' => $studentAlpha->getUser(),
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
            'global_bench' => $globalBench,
            'global_squat' => $globalSquat,
            'tenant_alpha' => $tenantAlpha,
            'owner_alpha' => $ownerAlpha,
            'membership_owner_alpha' => $ownerAlphaMembership,
            'trainer_alpha' => $userTrainerAlpha,
            'membership_trainer_alpha' => $memTrainerAlpha,
            'student_alpha' => $studentAlpha->getUser(),
            'membership_student_alpha' => $memStudentAlpha,
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

    public function testListExercisesUnifiesGlobalAndTenantSpecific(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];

        // 1. Owner cria um exercício customizado no Tenant Alpha
        $headersAlpha = $this->authHeaders($ctx['owner_alpha'], $ctx['tenant_alpha'], $ctx['membership_owner_alpha']);
        $client->request('POST', '/api/exercises', [], [], $headersAlpha, json_encode([
            'name' => 'Supino Inclinado com Halteres Alpha',
            'muscle_group' => 'CHEST',
            'equipment' => 'Halteres e Banco Inclinado',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Owner Beta cria um exercício customizado no Tenant Beta
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('POST', '/api/exercises', [], [], $headersBeta, json_encode([
            'name' => 'Exercício Exclusivo Beta',
            'muscle_group' => 'BACK',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 3. Usuário no Tenant Alpha lista exercícios
        $client->request('GET', '/api/exercises', [], [], $headersAlpha);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        $names = array_column($data['exercises'], 'name');

        // Deve conter os globais
        $this->assertContains('Supino Reto com Barra', $names);
        $this->assertContains('Agachamento Livre', $names);
        // Deve conter o customizado de Alpha
        $this->assertContains('Supino Inclinado com Halteres Alpha', $names);
        // NÃO PODE conter o exercício exclusivo de Beta (isolamento)
        $this->assertNotContains('Exercício Exclusivo Beta', $names);
    }

    public function testSearchAndFilterExercises(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['trainer_alpha'], $ctx['tenant_alpha'], $ctx['membership_trainer_alpha']);

        // Filtra por grupo muscular CHEST
        $client->request('GET', '/api/exercises?muscle_group=CHEST', [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);

        foreach ($data['exercises'] as $ex) {
            $this->assertSame('CHEST', $ex['muscle_group']);
        }

        // Filtra por termo de busca 'Agachamento'
        $client->request('GET', '/api/exercises?q=Agachamento', [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['exercises']);
        $this->assertSame('Agachamento Livre', $data['exercises'][0]['name']);
    }

    public function testStudentCannotCreateExercise(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['student_alpha'], $ctx['tenant_alpha'], $ctx['membership_student_alpha']);

        $client->request('POST', '/api/exercises', [], [], $headers, json_encode([
            'name' => 'Exercício Aluno',
            'muscle_group' => 'ARMS',
        ]));

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testExerciseLifecycleAndVersioning(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];
        $headers = $this->authHeaders($ctx['trainer_alpha'], $ctx['tenant_alpha'], $ctx['membership_trainer_alpha']);

        // 1. Cria exercício v1
        $client->request('POST', '/api/exercises', [], [], $headers, json_encode([
            'name' => 'Puxada Frontal com Barra',
            'muscle_group' => 'BACK',
            'equipment' => 'Polia Alta',
            'description' => 'Puxada na frente até o queixo',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $created = json_decode($client->getResponse()->getContent(), true);
        $exerciseId = $created['id'];
        $this->assertSame(1, $created['version']);

        // 2. Detalha o exercício
        $client->request('GET', '/api/exercises/' . $exerciseId, [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Puxada Frontal com Barra', $detail['name']);
        $this->assertSame(1, $detail['version']);

        // 3. Atualiza gerando nova versão v2
        $client->request('PATCH', '/api/exercises/' . $exerciseId, [], [], $headers, json_encode([
            'name' => 'Puxada Frontal com Triângulo',
            'description' => 'Pegada neutra fechada',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Puxada Frontal com Triângulo', $updated['name']);
        $this->assertSame(2, $updated['version']);

        // 4. Arquiva exercício
        $client->request('DELETE', '/api/exercises/' . $exerciseId, [], [], $headers);
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        // 5. Após arquivado, busca pelo ID não encontra
        $client->request('GET', '/api/exercises/' . $exerciseId, [], [], $headers);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testCrossTenantAccessReturns404(): void
    {
        $ctx = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $ctx['client'];

        // Cria no Tenant Beta
        $headersBeta = $this->authHeaders($ctx['owner_beta'], $ctx['tenant_beta'], $ctx['membership_owner_beta']);
        $client->request('POST', '/api/exercises', [], [], $headersBeta, json_encode([
            'name' => 'Secreto Beta',
            'muscle_group' => 'CORE',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $betaEx = json_decode($client->getResponse()->getContent(), true);

        // Usuário de Alpha tenta acessar exercício de Beta
        $headersAlpha = $this->authHeaders($ctx['owner_alpha'], $ctx['tenant_alpha'], $ctx['membership_owner_alpha']);
        $client->request('GET', '/api/exercises/' . $betaEx['id'], [], [], $headersAlpha);

        // Deve retornar 404 e NUNCA vazar a existência do ID
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
