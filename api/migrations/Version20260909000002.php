<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de identidade, multitenancy e atribuições (users, tenants, memberships, profiles, assignments)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id VARCHAR(36) NOT NULL,
            email VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(150) NOT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            is_platform_admin TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX uniq_users_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE tenants (
            id VARCHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            type VARCHAR(20) NOT NULL,
            document VARCHAR(30) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE tenant_memberships (
            id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            role VARCHAR(30) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_MEMBER_USER (user_id),
            INDEX IDX_MEMBER_TENANT (tenant_id),
            UNIQUE INDEX uniq_membership_user_tenant (user_id, tenant_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_MEMBER_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT FK_MEMBER_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE trainer_profiles (
            id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            cref VARCHAR(50) DEFAULT NULL,
            specialties JSON NOT NULL,
            bio TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_TRAINER_USER (user_id),
            INDEX IDX_TRAINER_TENANT (tenant_id),
            UNIQUE INDEX uniq_trainer_user_tenant (user_id, tenant_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_TRAINER_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT FK_TRAINER_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE student_profiles (
            id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            birth_date DATE DEFAULT NULL,
            gender VARCHAR(20) DEFAULT NULL,
            emergency_contact_name VARCHAR(150) DEFAULT NULL,
            emergency_contact_phone VARCHAR(30) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_STUDENT_USER (user_id),
            INDEX IDX_STUDENT_TENANT (tenant_id),
            UNIQUE INDEX uniq_student_user_tenant (user_id, tenant_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_STUDENT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT FK_STUDENT_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE trainer_student_assignments (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            trainer_id VARCHAR(36) NOT NULL,
            student_id VARCHAR(36) NOT NULL,
            is_primary TINYINT(1) NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_assignment_tenant_trainer (tenant_id, trainer_id),
            INDEX idx_assignment_tenant_student (tenant_id, student_id),
            INDEX IDX_ASSIGN_TRAINER (trainer_id),
            INDEX IDX_ASSIGN_STUDENT (student_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ASSIGN_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_ASSIGN_TRAINER FOREIGN KEY (trainer_id) REFERENCES trainer_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_ASSIGN_STUDENT FOREIGN KEY (student_id) REFERENCES student_profiles (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE trainer_student_assignments');
        $this->addSql('DROP TABLE student_profiles');
        $this->addSql('DROP TABLE trainer_profiles');
        $this->addSql('DROP TABLE tenant_memberships');
        $this->addSql('DROP TABLE tenants');
        $this->addSql('DROP TABLE users');
    }
}
