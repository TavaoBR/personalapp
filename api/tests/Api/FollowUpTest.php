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

final class FollowUpTest extends WebTestCase
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

    public function testCreateTemplateAndVersions(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);

        // 1. Criar Template de Acompanhamento Mensal
        $client->request('POST', '/api/follow-up-templates', [], [], $trainerHeaders, json_encode([
            'title' => 'Acompanhamento Mensal 30 Dias',
            'duration_days' => 30,
            'description' => 'Plano padrão com check-in quinzenal',
            'steps' => [
                ['title' => 'Anamnese Inicial', 'stepType' => 'ANAMNESIS', 'dayOffset' => 1],
                ['title' => 'Check-in Quinzenal', 'stepType' => 'CHECK_IN', 'dayOffset' => 15],
                ['title' => 'Avaliação Física Final', 'stepType' => 'ASSESSMENT', 'dayOffset' => 30],
            ],
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Acompanhamento Mensal 30 Dias', $res['title']);
        $this->assertSame(30, $res['duration_days']);
        $this->assertCount(1, $res['versions']);
        $this->assertCount(3, $res['versions'][0]['steps']);
        $templateId = $res['id'];

        // 2. Listar templates
        $client->request('GET', '/api/follow-up-templates', [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $listRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $listRes['templates']);

        // 3. Criar nova versão (V2)
        $client->request('POST', "/api/follow-up-templates/{$templateId}/versions", [], [], $trainerHeaders, json_encode([
            'steps' => [
                ['title' => 'Check-in Semanal 1', 'stepType' => 'CHECK_IN', 'dayOffset' => 7],
                ['title' => 'Check-in Semanal 2', 'stepType' => 'CHECK_IN', 'dayOffset' => 14],
                ['title' => 'Check-in Semanal 3', 'stepType' => 'CHECK_IN', 'dayOffset' => 21],
                ['title' => 'Reavaliação 30d', 'stepType' => 'ASSESSMENT', 'dayOffset' => 30],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $v2Res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(2, $v2Res['version_number']);
        $this->assertCount(4, $v2Res['steps']);
    }

    public function testPrescribePlanToStudentAndDeepCopy(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria template
        $client->request('POST', '/api/follow-up-templates', [], [], $trainerHeaders, json_encode([
            'title' => 'Protocolo 30d',
            'duration_days' => 30,
            'steps' => [
                ['title' => 'Etapa 1', 'stepType' => 'CHECK_IN', 'dayOffset' => 1],
                ['title' => 'Etapa 15', 'stepType' => 'CHECK_IN', 'dayOffset' => 15],
            ],
        ]));
        $tplRes = json_decode((string) $client->getResponse()->getContent(), true);
        $versionId = $tplRes['versions'][0]['id'];

        // 2. Prescreve plano com data de início 2026-03-01
        $client->request('POST', "/api/students/{$studentId}/follow-up-plans", [], [], $trainerHeaders, json_encode([
            'version_id' => $versionId,
            'start_date' => '2026-03-01',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $planRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Protocolo 30d', $planRes['title']);
        $this->assertSame('2026-03-01', $planRes['start_date']);
        $this->assertSame('2026-03-31', $planRes['end_date']);
        $this->assertCount(2, $planRes['tasks']);
        $this->assertSame('2026-03-01', $planRes['tasks'][0]['due_date']);
        $this->assertSame('2026-03-15', $planRes['tasks'][1]['due_date']);
        $taskId1 = $planRes['tasks'][0]['id'];

        // 3. Atualiza o status da tarefa para COMPLETED
        $client->request('PATCH', "/api/follow-up-tasks/{$taskId1}", [], [], $trainerHeaders, json_encode([
            'status' => 'COMPLETED',
            'notes' => 'Concluído com sucesso na data',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $taskRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('COMPLETED', $taskRes['status']);
        $this->assertNotNull($taskRes['completed_at']);
    }

    public function testCheckInSubmissionAndTrainerFeedback(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);
        $studentId = $data['studentProfile']->getId();

        // 1. Aluno envia check-in semanal
        $client->request('POST', "/api/students/{$studentId}/check-ins", [], [], $studentHeaders, json_encode([
            'check_in_date' => '2026-03-15',
            'energy_level' => 4,
            'sleep_quality' => 5,
            'soreness_level' => 2,
            'diet_adherence' => 5,
            'notes' => 'Semana excelente de treinos, dormindo bem.',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $checkInRes = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('SUBMITTED', $checkInRes['status']);
        $this->assertSame(4, $checkInRes['energy_level']);
        $checkInId = $checkInRes['id'];

        // 2. Treinador lista check-ins do aluno
        $client->request('GET', "/api/students/{$studentId}/check-ins", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $list = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $list['check_ins']);

        // 3. Treinador fornece feedback
        $client->request('POST', "/api/check-ins/{$checkInId}/feedback", [], [], $trainerHeaders, json_encode([
            'feedback' => 'Parabéns pela adesão! Vamos aumentar ligeiramente a carga no próximo microciclo.',
        ]));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $reviewed = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('REVIEWED', $reviewed['status']);
        $this->assertNotNull($reviewed['reviewed_at']);
        $this->assertSame('Parabéns pela adesão! Vamos aumentar ligeiramente a carga no próximo microciclo.', $reviewed['trainer_feedback']);

        // 4. Aluno visualiza em /api/me/check-ins
        $client->request('GET', '/api/me/check-ins', [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $meCheckIns = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $meCheckIns['check_ins']);
        $this->assertSame('REVIEWED', $meCheckIns['check_ins'][0]['status']);
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

        // 1. Treinador cria template e prescreve plano
        $client->request('POST', '/api/follow-up-templates', [], [], $trainerHeaders, json_encode([
            'title' => 'Plano Alpha Confidencial',
        ]));
        $tplRes = json_decode((string) $client->getResponse()->getContent(), true);
        $templateId = $tplRes['id'];

        $client->request('POST', "/api/students/{$studentId}/follow-up-plans", [], [], $trainerHeaders, json_encode([
            'title' => 'Plano Particular Aluno Alpha',
        ]));
        $planRes = json_decode((string) $client->getResponse()->getContent(), true);
        $planId = $planRes['id'];

        // 2. Treinador Beta tenta acessar o template de Alpha -> 404
        $client->request('GET', "/api/follow-up-templates/{$templateId}", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 3. Treinador Beta tenta acessar o plano do aluno de Alpha -> 404
        $client->request('GET', "/api/follow-up-plans/{$planId}", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 4. Treinador não atribuído tenta listar planos do aluno -> 404
        $client->request('GET', "/api/students/{$studentId}/follow-up-plans", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 5. Treinador não atribuído tenta ver detalhe do plano -> 404
        $client->request('GET', "/api/follow-up-plans/{$planId}", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
