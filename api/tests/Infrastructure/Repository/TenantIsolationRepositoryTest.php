<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Repository;

use App\Application\Service\TenantIdentityService;
use App\Domain\Entity\TenantMembership;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use App\Infrastructure\Repository\StudentProfileRepository;
use App\Infrastructure\Repository\TenantMembershipRepository;
use App\Infrastructure\Repository\TenantRepository;
use App\Infrastructure\Repository\TrainerProfileRepository;
use App\Infrastructure\Repository\TrainerStudentAssignmentRepository;
use App\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TenantIsolationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TenantIdentityService $identityService;
    private StudentProfileRepository $studentRepository;
    private TenantMembershipRepository $membershipRepository;
    private TrainerStudentAssignmentRepository $assignmentRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->identityService = $container->get(TenantIdentityService::class);
        $this->studentRepository = $container->get(StudentProfileRepository::class);
        $this->membershipRepository = $container->get(TenantMembershipRepository::class);
        $this->assignmentRepository = $container->get(TrainerStudentAssignmentRepository::class);

        // Recria o schema limpo para cada execução de teste
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testTenantIsolationForStudents(): void
    {
        // Cria Tenant A (Assessoria Alpha)
        $alpha = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Owner Alpha',
            'owner@alpha.com',
            'hash_alpha'
        );
        $tenantA = $alpha['tenant'];

        // Cria Tenant B (Assessoria Beta)
        $beta = $this->identityService->createTenantWithOwner(
            'Assessoria Beta',
            TenantType::ASSESSORIA,
            'Owner Beta',
            'owner@beta.com',
            'hash_beta'
        );
        $tenantB = $beta['tenant'];

        // Matricula Aluno 1 no Tenant A
        $studentA1 = $this->identityService->enrollStudent(
            $tenantA,
            'Aluno Alpha 1',
            'aluno1@alpha.com',
            'hash_student'
        );

        // Matricula Aluno 2 no Tenant B
        $studentB1 = $this->identityService->enrollStudent(
            $tenantB,
            'Aluno Beta 1',
            'aluno1@beta.com',
            'hash_student'
        );

        // 1. Validar que findByTenant em Tenant A retorna apenas Aluno A1
        $studentsInA = $this->studentRepository->findByTenant($tenantA);
        $this->assertCount(1, $studentsInA);
        $this->assertSame($studentA1->getId(), $studentsInA[0]->getId());

        // 2. Validar que findByTenant em Tenant B retorna apenas Aluno B1
        $studentsInB = $this->studentRepository->findByTenant($tenantB);
        $this->assertCount(1, $studentsInB);
        $this->assertSame($studentB1->getId(), $studentsInB[0]->getId());

        // 3. Validação de isolamento estrito cross-tenant (tentativa de buscar aluno do Tenant B informando Tenant A)
        $crossTenantLookup = $this->studentRepository->findByIdAndTenant($studentB1->getId(), $tenantA);
        $this->assertNull($crossTenantLookup, 'Consulta cross-tenant deve retornar NULL (isolamento estrito 404)');
    }

    public function testUserGlobalWithMultipleTenantMemberships(): void
    {
        // Valida ADR-002: mesmo User em múltiplos tenants com papéis distintos
        $alpha = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Treinador e Aluno',
            'multirole@example.com',
            'hash123'
        );
        $user = $alpha['user'];
        $tenantAlpha = $alpha['tenant'];

        $beta = $this->identityService->createTenantWithOwner(
            'Consultoria Beta',
            TenantType::PERSONAL,
            'Outro Personal',
            'personal@beta.com',
            'hash456'
        );
        $tenantBeta = $beta['tenant'];

        // Adiciona o mesmo usuário global como STUDENT no Tenant Beta
        $membershipBeta = new TenantMembership($user, $tenantBeta, TenantRole::STUDENT);
        $this->membershipRepository->save($membershipBeta, true);

        // Busca memberships do usuário global
        $memberships = $this->membershipRepository->findByUser($user);
        $this->assertCount(2, $memberships);

        $rolesByTenant = [];
        foreach ($memberships as $m) {
            $rolesByTenant[$m->getTenant()->getName()] = $m->getRole();
        }

        $this->assertSame(TenantRole::OWNER, $rolesByTenant['Assessoria Alpha']);
        $this->assertSame(TenantRole::STUDENT, $rolesByTenant['Consultoria Beta']);
    }

    public function testTrainerStudentAssignmentWithinTenant(): void
    {
        $alpha = $this->identityService->createTenantWithOwner(
            'Assessoria Alpha',
            TenantType::ASSESSORIA,
            'Head Coach',
            'coach@alpha.com',
            'hash_coach'
        );
        $tenant = $alpha['tenant'];
        $trainer = $alpha['trainerProfile'];

        $student = $this->identityService->enrollStudent(
            $tenant,
            'Aluno Atribuido',
            'aluno.atribuido@alpha.com',
            'hash_pass'
        );

        $assignment = $this->identityService->assignTrainerToStudent($tenant, $trainer, $student, true);
        $this->assertNotNull($assignment->getId());

        $activeByTrainer = $this->assignmentRepository->findActiveByTrainer($tenant, $trainer);
        $this->assertCount(1, $activeByTrainer);
        $this->assertSame($student->getId(), $activeByTrainer[0]->getStudent()->getId());
    }
}
