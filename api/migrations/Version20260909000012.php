<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas financeiras do Personal: coaching_plans, student_enrollments e student_payments';
    }

    public function up(Schema $schema): void
    {
        // 1. coaching_plans
        $this->addSql('CREATE TABLE coaching_plans (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            price DOUBLE PRECISION NOT NULL,
            billing_period VARCHAR(30) NOT NULL,
            duration_months INT NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_coaching_plan_tenant (tenant_id),
            INDEX idx_coaching_plan_status (tenant_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_COACHING_PLAN_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. student_enrollments
        $this->addSql('CREATE TABLE student_enrollments (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            coaching_plan_id VARCHAR(36) NOT NULL,
            price DOUBLE PRECISION NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            billing_day SMALLINT NOT NULL DEFAULT 10,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_enrollment_tenant (tenant_id),
            INDEX idx_enrollment_student (student_profile_id),
            INDEX idx_enrollment_status (student_profile_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_ENROLL_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_ENROLL_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_ENROLL_PLAN FOREIGN KEY (coaching_plan_id) REFERENCES coaching_plans (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. student_payments
        $this->addSql('CREATE TABLE student_payments (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            student_enrollment_id VARCHAR(36) DEFAULT NULL,
            amount DOUBLE PRECISION NOT NULL,
            due_date DATE NOT NULL,
            paid_at DATETIME DEFAULT NULL,
            payment_method VARCHAR(30) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'PENDING\',
            reference_period VARCHAR(30) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_payment_tenant (tenant_id),
            INDEX idx_payment_student (student_profile_id),
            INDEX idx_payment_status (tenant_id, status),
            INDEX idx_payment_due (tenant_id, due_date),
            PRIMARY KEY(id),
            CONSTRAINT FK_PAYMENT_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_PAYMENT_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_PAYMENT_ENROLLMENT FOREIGN KEY (student_enrollment_id) REFERENCES student_enrollments (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_payments');
        $this->addSql('DROP TABLE student_enrollments');
        $this->addSql('DROP TABLE coaching_plans');
    }
}
