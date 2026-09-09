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

final class TimelineTest extends WebTestCase
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

    public function testRecordAndRetrieveTimelineChronologically(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentId = $data['studentProfile']->getId();

        // 1. Inserir eventos na timeline com datas diferentes
        $eventsToCreate = [
            [
                'event_type' => 'STUDENT_ENROLLED',
                'title' => 'Aluno Matriculado',
                'occurred_at' => '2026-01-01T09:00:00Z',
                'description' => 'Início da consultoria online',
            ],
            [
                'event_type' => 'WORKOUT_COMPLETED',
                'title' => 'Treino A Concluído',
                'occurred_at' => '2026-01-03T18:30:00Z',
                'entity_type' => 'WorkoutSession',
                'entity_id' => 'session-123',
                'metadata' => ['duration_seconds' => 3600, 'completion_rate' => 100.0],
            ],
            [
                'event_type' => 'ASSESSMENT_RECORDED',
                'title' => 'Primeira Avaliação Física',
                'occurred_at' => '2026-01-10T11:00:00Z',
                'entity_type' => 'PhysicalAssessment',
                'entity_id' => 'assess-123',
                'metadata' => ['weight' => 85.0, 'body_fat' => 22.5],
            ],
            [
                'event_type' => 'GOAL_ACHIEVED',
                'title' => 'Meta 75kg Conquistada',
                'occurred_at' => '2026-03-15T10:00:00Z',
                'entity_type' => 'StudentGoal',
                'entity_id' => 'goal-123',
            ],
        ];

        foreach ($eventsToCreate as $evt) {
            $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode($evt));
            $this->assertSame(201, $client->getResponse()->getStatusCode());
        }

        // 2. Recuperar timeline -> deve estar em ordem decrescente de occurred_at
        $client->request('GET', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertSame(4, $res['total']);
        $this->assertCount(4, $res['events']);

        // Primeiro item deve ser o mais recente (GOAL_ACHIEVED em Março)
        $this->assertSame('GOAL_ACHIEVED', $res['events'][0]['event_type']);
        $this->assertSame('ASSESSMENT_RECORDED', $res['events'][1]['event_type']);
        $this->assertSame('WORKOUT_COMPLETED', $res['events'][2]['event_type']);
        $this->assertSame('STUDENT_ENROLLED', $res['events'][3]['event_type']);

        // Valida metadados e entidade vinculada
        $this->assertSame('session-123', $res['events'][2]['entity_id']);
        $this->assertSame(3600, $res['events'][2]['metadata']['duration_seconds']);
    }

    public function testTimelineFilterByTypeAndDateRange(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentId = $data['studentProfile']->getId();

        // 1. Cria 3 treinos e 1 avaliação
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'WORKOUT_COMPLETED',
            'title' => 'Treino A',
            'occurred_at' => '2026-02-01T10:00:00Z',
        ]));
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'WORKOUT_COMPLETED',
            'title' => 'Treino B',
            'occurred_at' => '2026-02-03T10:00:00Z',
        ]));
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'WORKOUT_COMPLETED',
            'title' => 'Treino C',
            'occurred_at' => '2026-02-05T10:00:00Z',
        ]));
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'ASSESSMENT_RECORDED',
            'title' => 'Avaliação',
            'occurred_at' => '2026-02-10T10:00:00Z',
        ]));

        // 2. Filtra por event_type=WORKOUT_COMPLETED
        $client->request('GET', "/api/students/{$studentId}/timeline?event_type=WORKOUT_COMPLETED", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resFilterType = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(3, $resFilterType['total']);
        $this->assertCount(3, $resFilterType['events']);

        // 3. Filtra por intervalo de datas (2026-02-02 a 2026-02-04)
        $client->request('GET', "/api/students/{$studentId}/timeline?start_date=2026-02-02&end_date=2026-02-04", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resFilterDate = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $resFilterDate['total']);
        $this->assertSame('Treino B', $resFilterDate['events'][0]['title']);

        // 4. Paginação
        $client->request('GET', "/api/students/{$studentId}/timeline?limit=2&offset=0", [], [], $trainerHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $resPage1 = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(4, $resPage1['total']);
        $this->assertCount(2, $resPage1['events']);
    }

    public function testStudentMeTimeline(): void
    {
        $data = $this->setupScenario();
        /** @var KernelBrowser $client */
        $client = $data['client'];
        $trainerHeaders = $this->getAuthHeaders($data['trainerUser'], $data['tenantAlpha'], $data['membershipTrainer']);
        $studentHeaders = $this->getAuthHeaders($data['studentUser'], $data['tenantAlpha'], $data['membershipStudent']);
        $studentId = $data['studentProfile']->getId();

        // 1. Treinador insere evento
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'NOTE_ADDED',
            'title' => 'Feedback Semanal do Treinador',
            'description' => 'Execução perfeita do supino reto.',
            'occurred_at' => '2026-02-15T15:00:00Z',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Aluno consulta /api/me/timeline
        $client->request('GET', '/api/me/timeline', [], [], $studentHeaders);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $res['total']);
        $this->assertSame('Feedback Semanal do Treinador', $res['events'][0]['title']);
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

        // 1. Treinador de Alpha registra evento
        $client->request('POST', "/api/students/{$studentId}/timeline", [], [], $trainerHeaders, json_encode([
            'event_type' => 'NOTE_ADDED',
            'title' => 'Nota Restrita',
        ]));
        $this->assertSame(201, $client->getResponse()->getStatusCode());

        // 2. Treinador Beta tenta acessar a timeline do aluno de Alpha -> 404
        $client->request('GET', "/api/students/{$studentId}/timeline", [], [], $betaTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // 3. Treinador não atribuído no mesmo tenant tenta acessar a timeline -> 404
        $client->request('GET', "/api/students/{$studentId}/timeline", [], [], $unassignedTrainerHeaders);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
