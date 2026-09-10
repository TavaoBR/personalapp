<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\User;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TenantIdentityApiTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    private function setupFixtures(KernelBrowser $client): array
    {
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->hasher = $container->get(UserPasswordHasherInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // Tenant Alpha
        $tenantAlpha = new Tenant('Alpha Academy', TenantType::ASSESSORIA);
        $this->entityManager->persist($tenantAlpha);

        // Tenant Beta (Isolado)
        $tenantBeta = new Tenant('Beta Fitness', TenantType::PERSONAL);
        $this->entityManager->persist($tenantBeta);

        // Owner Alpha
        $owner = new User('owner@alpha.com', '', 'Owner Alpha');
        $owner->setPasswordHash($this->hasher->hashPassword($owner, 'password123'));
        $this->entityManager->persist($owner);
        $this->entityManager->persist(new TenantMembership($owner, $tenantAlpha, TenantRole::OWNER));
        $ownerTrainerProfile = new TrainerProfile($owner, $tenantAlpha, null, 'CREF-1111/SP', ['Musculação']);
        $this->entityManager->persist($ownerTrainerProfile);

        // Trainer Alpha
        $trainer = new User('trainer@alpha.com', '', 'Coach John');
        $trainer->setPasswordHash($this->hasher->hashPassword($trainer, 'password123'));
        $this->entityManager->persist($trainer);
        $this->entityManager->persist(new TenantMembership($trainer, $tenantAlpha, TenantRole::TRAINER));
        $trainerProfile = new TrainerProfile($trainer, $tenantAlpha, null, 'CREF-2222/SP', ['Hipertrofia', 'Corrida']);
        $this->entityManager->persist($trainerProfile);

        // Student Alpha
        $studentUser = new User('student@alpha.com', '', 'Student Alice');
        $studentUser->setPasswordHash($this->hasher->hashPassword($studentUser, 'password123'));
        $this->entityManager->persist($studentUser);
        $this->entityManager->persist(new TenantMembership($studentUser, $tenantAlpha, TenantRole::STUDENT));
        $studentProfile = new StudentProfile($studentUser, $tenantAlpha);
        $this->entityManager->persist($studentProfile);

        $this->entityManager->flush();

        return [
            'tenantAlpha' => $tenantAlpha,
            'tenantBeta' => $tenantBeta,
            'owner' => $owner,
            'trainer' => $trainer,
            'trainerProfile' => $trainerProfile,
            'studentUser' => $studentUser,
            'studentProfile' => $studentProfile,
        ];
    }

    private function authenticate(KernelBrowser $client, string $email, string $password, string $tenantId): string
    {
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password, 'tenantId' => $tenantId])
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['access_token'] ?? '';
    }

    public function testListAndCreateTrainers(): void
    {
        $client = static::createClient();
        $fixtures = $this->setupFixtures($client);

        $token = $this->authenticate($client, 'owner@alpha.com', 'password123', $fixtures['tenantAlpha']->getId());

        // 1. Listar treinadores do tenant
        $client->request('GET', '/api/trainers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
        ]);
        $this->assertResponseIsSuccessful();
        $listData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('trainers', $listData);
        $this->assertCount(2, $listData['trainers']); // Owner + Trainer

        // 2. Cadastrar novo treinador
        $client->request('POST', '/api/trainers', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Coach Marcos',
            'email' => 'marcos@alpha.com',
            'cref' => 'CREF-9999/SP',
            'specialties' => ['Funcional', 'LPO'],
            'bio' => 'Especialista em condicionamento físico avançado.',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $createdData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Coach Marcos', $createdData['user']['name']);
        $this->assertSame('CREF-9999/SP', $createdData['cref']);
    }

    public function testEnrollStudentAndAssignTrainer(): void
    {
        $client = static::createClient();
        $fixtures = $this->setupFixtures($client);

        $token = $this->authenticate($client, 'owner@alpha.com', 'password123', $fixtures['tenantAlpha']->getId());

        // 1. Matricular novo aluno com treinador atribuído
        $client->request('POST', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Carlos Ferreira',
            'email' => 'carlos@gmail.com',
            'gender' => 'M',
            'birth_date' => '1995-08-20',
            'phone' => '11999998888',
            'emergency_contact_name' => 'Maria Ferreira',
            'emergency_contact_phone' => '11988887777',
            'notes' => 'Objetivo: perda de gordura e hipertrofia.',
            'trainer_id' => $fixtures['trainerProfile']->getId(),
        ]));
        $this->assertResponseStatusCodeSame(201);
        $newStudent = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Carlos Ferreira', $newStudent['name']);
        $this->assertNotNull($newStudent['trainer']);
        $this->assertSame($fixtures['trainerProfile']->getId(), $newStudent['trainer']['id']);

        $studentId = $newStudent['id'];

        // 2. Listar alunos e verificar que o treinador vem populado
        $client->request('GET', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
        ]);
        $this->assertResponseIsSuccessful();
        $studentsList = json_decode($client->getResponse()->getContent(), true)['students'];
        $this->assertCount(2, $studentsList); // Alice + Carlos

        // 3. Detalhe do aluno
        $client->request('GET', '/api/students/' . $studentId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
        ]);
        $this->assertResponseIsSuccessful();
        $detail = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Maria Ferreira', $detail['emergency_contact_name']);
        $this->assertSame('Carlos Ferreira', $detail['user']['name']);
        $this->assertSame('Coach John', $detail['trainer']['name']);
    }

    public function testStudentCannotListOrEnrollStudents(): void
    {
        $client = static::createClient();
        $fixtures = $this->setupFixtures($client);

        $studentToken = $this->authenticate($client, 'student@alpha.com', 'password123', $fixtures['tenantAlpha']->getId());

        // Aluno tentando listar alunos
        $client->request('GET', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Aluno tentando cadastrar aluno
        $client->request('POST', '/api/students', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            'HTTP_X_TENANT_ID' => $fixtures['tenantAlpha']->getId(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Hacker', 'email' => 'hacker@alpha.com']));
        $this->assertResponseStatusCodeSame(403);
    }
}
