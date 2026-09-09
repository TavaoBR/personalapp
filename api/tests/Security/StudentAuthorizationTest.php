<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Application\Service\TenantIdentityService;
use App\Application\Service\TokenService;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use App\Infrastructure\Repository\TenantMembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class StudentAuthorizationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;
    private TokenService $tokenService;
    private TenantIdentityService $identityService;
    private TenantMembershipRepository $membershipRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    private function setupScenario(): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);
        $this->tokenService = $container->get(TokenService::class);
        $this->identityService = $container->get(TenantIdentityService::class);
        $this->membershipRepository = $container->get(TenantMembershipRepository::class);

        // Reset schema
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 1. Tenant Alpha (Assessoria)
        $alpha = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'hash'
        );
        $tenantAlpha = $alpha['tenant'];
        $ownerAlpha = $alpha['user'];
        $ownerMembershipAlpha = $alpha['membership'];

        // 2. Treinador 1 no Tenant Alpha
        $userTrainer1 = new User('trainer1@alpha.com', 'hash', 'Trainer One');
        $this->entityManager->persist($userTrainer1);
        $membershipTrainer1 = new TenantMembership($userTrainer1, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipTrainer1);
        $trainerProfile1 = new TrainerProfile($userTrainer1, $tenantAlpha, null, 'CREF-001');
        $this->entityManager->persist($trainerProfile1);

        // 3. Treinador 2 no Tenant Alpha
        $userTrainer2 = new User('trainer2@alpha.com', 'hash', 'Trainer Two');
        $this->entityManager->persist($userTrainer2);
        $membershipTrainer2 = new TenantMembership($userTrainer2, $tenantAlpha, TenantRole::TRAINER);
        $this->entityManager->persist($membershipTrainer2);
        $trainerProfile2 = new TrainerProfile($userTrainer2, $tenantAlpha, null, 'CREF-002');
        $this->entityManager->persist($trainerProfile2);

        // 4. Aluno 1 no Tenant Alpha (atribuído ao Treinador 1)
        $studentProfile1 = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha Um',
            'student1@alpha.com',
            'hash'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile1, $studentProfile1);

        // 5. Aluno 2 no Tenant Alpha (atribuído ao Treinador 2)
        $studentProfile2 = $this->identityService->enrollStudent(
            $tenantAlpha,
            'Aluno Alpha Dois',
            'student2@alpha.com',
            'hash'
        );
        $this->identityService->assignTrainerToStudent($tenantAlpha, $trainerProfile2, $studentProfile2);

        // 6. Tenant Beta (Outro Personal independente)
        $beta = $this->identityService->createTenantWithOwner(
            'Personal Beta',
            TenantType::PERSONAL,
            'Owner Beta',
            'owner@beta.com',
            'hash'
        );
        $tenantBeta = $beta['tenant'];
        $studentProfileBeta = $this->identityService->enrollStudent(
            $tenantBeta,
            'Aluno Beta',
            'student@beta.com',
            'hash'
        );

        $this->entityManager->flush();

        return [
            'client' => $client,
            'tenantAlpha' => $tenantAlpha,
            'ownerAlpha' => $ownerAlpha,
            'ownerMembershipAlpha' => $ownerMembershipAlpha,
            'userTrainer1' => $userTrainer1,
            'membershipTrainer1' => $membershipTrainer1,
            'trainerProfile1' => $trainerProfile1,
            'userTrainer2' => $userTrainer2,
            'membershipTrainer2' => $membershipTrainer2,
            'trainerProfile2' => $trainerProfile2,
            'studentProfile1' => $studentProfile1,
            'studentProfile2' => $studentProfile2,
            'tenantBeta' => $tenantBeta,
            'studentProfileBeta' => $studentProfileBeta,
        ];
    }

    public function testOwnerCanViewAllStudentsInTenant(): void
    {
        $data = $this->setupScenario();
        $client = $data['client'];

        $token = $this->tokenService->createAccessToken($data['ownerAlpha'], $data['tenantAlpha'], $data['ownerMembershipAlpha']);

        // Owner lista alunos -> deve ver os 2 alunos da assessoria
        $client->request('GET', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $res['students']);

        // Owner acessa detalhe do Aluno 1 -> 200 OK
        $client->request('GET', '/api/students/' . $data['studentProfile1']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testTrainerCanOnlyAccessAssignedStudents(): void
    {
        $data = $this->setupScenario();
        $client = $data['client'];

        $tokenTrainer1 = $this->tokenService->createAccessToken(
            $data['userTrainer1'],
            $data['tenantAlpha'],
            $data['membershipTrainer1']
        );

        // 1. Trainer 1 lista alunos -> deve ver APENAS Aluno 1 (atribuído a ele)
        $client->request('GET', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenTrainer1),
        ]);
        $this->assertResponseIsSuccessful();
        $res = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $res['students']);
        $this->assertSame($data['studentProfile1']->getId(), $res['students'][0]['id']);

        // 2. Trainer 1 acessa Aluno 1 (atribuído) -> 200 OK
        $client->request('GET', '/api/students/' . $data['studentProfile1']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenTrainer1),
        ]);
        $this->assertResponseIsSuccessful();

        // 3. Trainer 1 acessa Aluno 2 (NÃO atribuído) -> deve retornar estritamente 404 NOT FOUND
        $client->request('GET', '/api/students/' . $data['studentProfile2']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenTrainer1),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testStudentCannotListStudentsAndCanOnlyViewOwnProfile(): void
    {
        $data = $this->setupScenario();
        $client = $data['client'];

        $studentUser1 = $data['studentProfile1']->getUser();
        $membershipStudent1 = $this->membershipRepository->findByUserAndTenant($studentUser1, $data['tenantAlpha']);

        $tokenStudent1 = $this->tokenService->createAccessToken(
            $studentUser1,
            $data['tenantAlpha'],
            $membershipStudent1
        );

        // 1. Aluno tenta listar todos os alunos -> 403 Forbidden
        $client->request('GET', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenStudent1),
        ]);
        $this->assertResponseStatusCodeSame(403);

        // 2. Aluno 1 acessa seu próprio perfil -> 200 OK
        $client->request('GET', '/api/students/' . $data['studentProfile1']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenStudent1),
        ]);
        $this->assertResponseIsSuccessful();

        // 3. Aluno 1 tenta acessar o perfil do Aluno 2 -> deve retornar estritamente 404 NOT FOUND
        $client->request('GET', '/api/students/' . $data['studentProfile2']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenStudent1),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCrossTenantAccessToStudentReturns404(): void
    {
        $data = $this->setupScenario();
        $client = $data['client'];

        // Token do Owner de Tenant Alpha
        $tokenAlpha = $this->tokenService->createAccessToken(
            $data['ownerAlpha'],
            $data['tenantAlpha'],
            $data['ownerMembershipAlpha']
        );

        // Owner de Alpha tenta acessar aluno que pertence a Tenant Beta -> 404 Not Found!
        $client->request('GET', '/api/students/' . $data['studentProfileBeta']->getId(), [], [], [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $tokenAlpha),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }
}
