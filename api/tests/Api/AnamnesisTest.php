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

final class AnamnesisTest extends WebTestCase
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

        // Unassigned trainer
        $otherTrainerUser = new User('othertrainer@alpha.com', 'pass123', 'Treinador Outro');
        $this->entityManager->persist($otherTrainerUser);
        $membershipOther = new TenantMembership($otherTrainerUser, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipOther);
        $otherTrainerProfile = new TrainerProfile($otherTrainerUser, $tenantAlpha, null, 'CREF-002');
        $this->entityManager->persist($otherTrainerProfile);

        // Student 1 assigned to trainerProfile
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

    public function testCreateTemplateAndVersion(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $headers = $this->getAuthHeaders($data['ownerUser'], $data['tenantAlpha'], $data['membershipOwner']);

        // 1. Criar Template de Anamnese
        $client->request('POST', '/api/anamnesis-templates', [], [], $headers, json_encode([
            'title' => 'Anamnese Inicial Padrão',
            'description' => 'Questionário inicial de saúde e hábitos',
            'questions' => [
                [
                    'text' => 'Possui alguma lesão ou limitação física?',
                    'type' => 'TEXT',
                    'required' => true,
                    'orderIndex' => 1,
                ],
                [
                    'text' => 'Pratica atividade física regularmente?',
                    'type' => 'YES_NO',
                    'required' => true,
                    'orderIndex' => 2,
                ],
                [
                    'text' => 'Nível de dor articular habitual (0 a 10)',
                    'type' => 'SCALE',
                    'required' => false,
                    'orderIndex' => 3,
                    'configuration' => ['min' => 0, 'max' => 10],
                ],
            ],
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $res = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $res);
        $this->assertSame('Anamnese Inicial Padrão', $res['title']);
        $this->assertCount(1, $res['versions']);
        $this->assertCount(3, $res['versions'][0]['questions']);
        $templateId = $res['id'];
        $v1Id = $res['versions'][0]['id'];

        // 2. Listar templates
        $client->request('GET', '/api/anamnesis-templates', [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $listRes = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(1, $listRes['templates']);
        $this->assertSame('Anamnese Inicial Padrão', $listRes['templates'][0]['title']);

        // 3. Criar nova versão (V2) para o template
        $client->request('POST', "/api/anamnesis-templates/{$templateId}/versions", [], [], $headers, json_encode([
            'questions' => [
                [
                    'text' => 'Possui histórico familiar de doenças cardíacas?',
                    'type' => 'YES_NO',
                    'required' => true,
                ],
                [
                    'text' => 'Objetivo principal com os treinos',
                    'type' => 'SINGLE_SELECT',
                    'configuration' => ['options' => ['Hipertrofia', 'Emagrecimento', 'Saúde']],
                ],
            ],
        ]));

        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $v2Res = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertSame(2, $v2Res['version_number']);
        $this->assertCount(2, $v2Res['questions']);

        // 4. Detalhe do template exibe ambas as versões
        $client->request('GET', "/api/anamnesis-templates/{$templateId}", [], [], $headers);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detailRes = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(2, $detailRes['versions']);
    }

    public function testSubmitAnamnesisAndPreserveHistory(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);

        // 1. Treinador cria template
        $client->request('POST', '/api/anamnesis-templates', [], [], $trainerHeaders, json_encode([
            'title' => 'Anamnese de Saúde',
            'questions' => [
                ['text' => 'Frequência semanal pretendida', 'type' => 'NUMBER', 'required' => true],
                ['text' => 'Medicamentos de uso contínuo', 'type' => 'TEXT', 'required' => false],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $tplRes = json_decode((string)$client->getResponse()->getContent(), true);
        $versionId = $tplRes['versions'][0]['id'];
        $q1Id = $tplRes['versions'][0]['questions'][0]['id'];
        $q2Id = $tplRes['versions'][0]['questions'][1]['id'];

        $studentId = $data['studentProfile']->getId();

        // 2. Aluno preenche a anamnese 1 (Janeiro)
        $client->request('POST', "/api/students/{$studentId}/anamneses", [], [], $studentHeaders, json_encode([
            'version_id' => $versionId,
            'answered_at' => '2026-01-10T10:00:00Z',
            'notes' => 'Preenchimento no início do plano',
            'answers' => [
                ['questionId' => $q1Id, 'value' => '3'],
                ['questionId' => $q2Id, 'value' => 'Nenhum'],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $ans1Res = json_decode((string)$client->getResponse()->getContent(), true);
        $anamnesis1Id = $ans1Res['id'];
        $this->assertCount(2, $ans1Res['answers']);
        $this->assertSame('3', $ans1Res['answers'][0]['answer']);
        $this->assertSame('Frequência semanal pretendida', $ans1Res['answers'][0]['question_text']);

        // 3. Aluno preenche a anamnese 2 (Março) - Histórico preservado, sem sobrescrever a primeira
        $client->request('POST', "/api/students/{$studentId}/anamneses", [], [], $studentHeaders, json_encode([
            'version_id' => $versionId,
            'answered_at' => '2026-03-10T10:00:00Z',
            'notes' => 'Reavaliação trimestral',
            'answers' => [
                ['questionId' => $q1Id, 'value' => '5'],
                ['questionId' => $q2Id, 'value' => 'Vitamina D'],
            ],
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $ans2Res = json_decode((string)$client->getResponse()->getContent(), true);
        $anamnesis2Id = $ans2Res['id'];
        $this->assertNotSame($anamnesis1Id, $anamnesis2Id);

        // 4. Treinador lista o histórico de anamneses do aluno -> deve ter 2 registros
        $client->request('GET', "/api/students/{$studentId}/anamneses", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $listRes = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(2, $listRes['anamneses']);

        // 5. Aluno consulta seu próprio histórico em /api/me/anamneses
        $client->request('GET', '/api/me/anamneses', [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $meRes = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertCount(2, $meRes['anamneses']);

        // 6. Detalhe de cada anamnese mantém valores e snapshots intactos
        $client->request('GET', "/api/anamneses/{$anamnesis1Id}", [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detail1 = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertSame('Preenchimento no início do plano', $detail1['notes']);
        $this->assertSame('3', $detail1['answers'][0]['answer']);

        $client->request('GET', "/api/anamneses/{$anamnesis2Id}", [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $detail2 = json_decode((string)$client->getResponse()->getContent(), true);
        $this->assertSame('Reavaliação trimestral', $detail2['notes']);
        $this->assertSame('5', $detail2['answers'][0]['answer']);
    }

    public function testSecurityAndTenantIsolationAntiEnumeration(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $unassignedTrainerHeaders = $this->getAuthHeaders($data['otherTrainerUser'], $data['tenantAlpha'], $data['membershipOtherTrainer']);
        $betaTrainerHeaders = $this->getAuthHeaders($data['betaTrainerUser'], $data['tenantBeta'], $data['membershipBetaTrainer']);

        // 1. Cria template e submete anamnese em Alpha
        $client->request('POST', '/api/anamnesis-templates', [], [], $trainerHeaders, json_encode([
            'title' => 'Template Confidencial',
            'questions' => [['text' => 'Pergunta A', 'type' => 'TEXT']],
        ]));
        $tplRes = json_decode((string)$client->getResponse()->getContent(), true);
        $templateId = $tplRes['id'];
        $versionId = $tplRes['versions'][0]['id'];

        $studentId = $data['studentProfile']->getId();
        $client->request('POST', "/api/students/{$studentId}/anamneses", [], [], $trainerHeaders, json_encode([
            'version_id' => $versionId,
            'answers' => [['questionText' => 'Pergunta A', 'value' => 'Resposta Secreta']],
        ]));
        $ansRes = json_decode((string)$client->getResponse()->getContent(), true);
        $anamnesisId = $ansRes['id'];

        // 2. Treinador de Tenant Beta tenta acessar o template do Tenant Alpha -> 404 (anti-enumeração)
        $client->request('GET', "/api/anamnesis-templates/{$templateId}", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 3. Treinador de Tenant Beta tenta acessar a anamnese do aluno do Tenant Alpha -> 404
        $client->request('GET', "/api/anamneses/{$anamnesisId}", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 4. Treinador não atribuído no mesmo tenant tenta acessar as anamneses do aluno -> 404
        $client->request('GET', "/api/students/{$studentId}/anamneses", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 5. Treinador não atribuído tenta acessar o detalhe da anamnese -> 404
        $client->request('GET', "/api/anamneses/{$anamnesisId}", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
