<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Entity\StudentProfile;
use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantMembership;
use App\Domain\Entity\TrainerProfile;
use App\Domain\Entity\TrainerStudentAssignment;
use App\Domain\Entity\User;
use App\Domain\Enum\AssignmentStatus;
use App\Domain\Enum\MembershipStatus;
use App\Domain\Enum\TenantRole;
use App\Domain\Enum\TenantType;
use App\Domain\Enum\UserStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TenantIdentityTest extends TestCase
{
    public function testUserCreationAndProperties(): void
    {
        $user = new User('trainer@example.com', 'hash123', 'John Doe');

        $this->assertNotEmpty($user->getId());
        $this->assertSame('trainer@example.com', $user->getEmail());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame(UserStatus::ACTIVE, $user->getStatus());
        $this->assertFalse($user->isPlatformAdmin());
        $this->assertCount(0, $user->getMemberships());
    }

    public function testTenantCreation(): void
    {
        $tenantPersonal = new Tenant('Personal Carlos', TenantType::PERSONAL);
        $tenantAssessoria = new Tenant('Assessoria Iron', TenantType::ASSESSORIA);

        $this->assertNotEmpty($tenantPersonal->getId());
        $this->assertSame('Personal Carlos', $tenantPersonal->getName());
        $this->assertSame(TenantType::PERSONAL, $tenantPersonal->getType());
        $this->assertSame(TenantType::ASSESSORIA, $tenantAssessoria->getType());
    }

    public function testTenantMembershipRelationship(): void
    {
        $user = new User('owner@example.com', 'hash', 'Owner User');
        $tenant = new Tenant('Assessoria Elite', TenantType::ASSESSORIA);

        $membership = new TenantMembership($user, $tenant, TenantRole::OWNER);

        $this->assertSame($user, $membership->getUser());
        $this->assertSame($tenant, $membership->getTenant());
        $this->assertSame(TenantRole::OWNER, $membership->getRole());
        $this->assertSame(MembershipStatus::ACTIVE, $membership->getStatus());
    }

    public function testTrainerStudentAssignmentSameTenantSucceeds(): void
    {
        $tenant = new Tenant('Assessoria Alpha', TenantType::ASSESSORIA);
        $userTrainer = new User('trainer@alpha.com', 'hash', 'Trainer Alpha');
        $userStudent = new User('student@alpha.com', 'hash', 'Student Alpha');

        $trainerProfile = new TrainerProfile($userTrainer, $tenant, null, 'CREF-123456');
        $studentProfile = new StudentProfile($userStudent, $tenant);

        $assignment = new TrainerStudentAssignment($tenant, $trainerProfile, $studentProfile, true);

        $this->assertSame($tenant, $assignment->getTenant());
        $this->assertSame($trainerProfile, $assignment->getTrainer());
        $this->assertSame($studentProfile, $assignment->getStudent());
        $this->assertTrue($assignment->isPrimary());
        $this->assertSame(AssignmentStatus::ACTIVE, $assignment->getStatus());

        $assignment->endAssignment();
        $this->assertSame(AssignmentStatus::ENDED, $assignment->getStatus());
        $this->assertNotNull($assignment->getEndedAt());
    }

    public function testTrainerStudentAssignmentDifferentTenantThrowsException(): void
    {
        $tenantA = new Tenant('Assessoria A', TenantType::ASSESSORIA);
        $tenantB = new Tenant('Assessoria B', TenantType::ASSESSORIA);

        $userTrainer = new User('trainer@a.com', 'hash', 'Trainer A');
        $userStudent = new User('student@b.com', 'hash', 'Student B');

        $trainerProfile = new TrainerProfile($userTrainer, $tenantA);
        $studentProfile = new StudentProfile($userStudent, $tenantB); // Student belongs to Tenant B!

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O aluno não pertence ao tenant especificado.');

        // Attempt to assign Student B to Trainer A inside Tenant A
        new TrainerStudentAssignment($tenantA, $trainerProfile, $studentProfile);
    }
}
