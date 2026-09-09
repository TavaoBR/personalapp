<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de planos de acompanhamento, tarefas e check-ins';
    }

    public function up(Schema $schema): void
    {
        // 1. follow_up_plan_templates
        $this->addSql('CREATE TABLE follow_up_plan_templates (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            duration_days INT NOT NULL DEFAULT 30,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_follow_up_template_tenant (tenant_id),
            INDEX idx_follow_up_template_status (tenant_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_FOLLOW_UP_TPL_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. follow_up_plan_versions
        $this->addSql('CREATE TABLE follow_up_plan_versions (
            id VARCHAR(36) NOT NULL,
            follow_up_plan_template_id VARCHAR(36) NOT NULL,
            version_number INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'PUBLISHED\',
            created_at DATETIME NOT NULL,
            published_at DATETIME DEFAULT NULL,
            INDEX idx_follow_up_ver_lookup (follow_up_plan_template_id, version_number),
            PRIMARY KEY(id),
            CONSTRAINT FK_FOLLOW_UP_VER_TPL FOREIGN KEY (follow_up_plan_template_id) REFERENCES follow_up_plan_templates (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. follow_up_plan_steps
        $this->addSql('CREATE TABLE follow_up_plan_steps (
            id VARCHAR(36) NOT NULL,
            follow_up_plan_version_id VARCHAR(36) NOT NULL,
            title VARCHAR(150) NOT NULL,
            step_type VARCHAR(30) NOT NULL,
            day_offset INT NOT NULL DEFAULT 1,
            order_index INT NOT NULL DEFAULT 0,
            description LONGTEXT DEFAULT NULL,
            configuration JSON DEFAULT NULL,
            INDEX idx_follow_up_step_version (follow_up_plan_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_FOLLOW_UP_STEP_VER FOREIGN KEY (follow_up_plan_version_id) REFERENCES follow_up_plan_versions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. student_follow_up_plans
        $this->addSql('CREATE TABLE student_follow_up_plans (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            trainer_profile_id VARCHAR(36) DEFAULT NULL,
            follow_up_plan_version_id VARCHAR(36) DEFAULT NULL,
            title VARCHAR(150) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_follow_up_tenant (tenant_id),
            INDEX idx_student_follow_up_student (student_profile_id),
            INDEX idx_student_follow_up_status (student_profile_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_STU_FOLLOW_UP_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_STU_FOLLOW_UP_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_STU_FOLLOW_UP_TRAINER FOREIGN KEY (trainer_profile_id) REFERENCES trainer_profiles (id) ON DELETE SET NULL,
            CONSTRAINT FK_STU_FOLLOW_UP_VER FOREIGN KEY (follow_up_plan_version_id) REFERENCES follow_up_plan_versions (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. student_follow_up_tasks
        $this->addSql('CREATE TABLE student_follow_up_tasks (
            id VARCHAR(36) NOT NULL,
            student_follow_up_plan_id VARCHAR(36) NOT NULL,
            title VARCHAR(150) NOT NULL,
            task_type VARCHAR(30) NOT NULL,
            due_date DATE NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'PENDING\',
            order_index INT NOT NULL DEFAULT 0,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_follow_up_task_plan (student_follow_up_plan_id),
            INDEX idx_follow_up_task_due (student_follow_up_plan_id, due_date),
            PRIMARY KEY(id),
            CONSTRAINT FK_TASK_FOLLOW_UP_PLAN FOREIGN KEY (student_follow_up_plan_id) REFERENCES student_follow_up_plans (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. student_check_ins
        $this->addSql('CREATE TABLE student_check_ins (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            student_follow_up_task_id VARCHAR(36) DEFAULT NULL,
            check_in_date DATE NOT NULL,
            energy_level SMALLINT DEFAULT NULL,
            sleep_quality SMALLINT DEFAULT NULL,
            soreness_level SMALLINT DEFAULT NULL,
            diet_adherence SMALLINT DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            trainer_feedback LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'SUBMITTED\',
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_check_in_tenant (tenant_id),
            INDEX idx_check_in_student_date (student_profile_id, check_in_date),
            PRIMARY KEY(id),
            CONSTRAINT FK_CHECK_IN_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_CHECK_IN_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_CHECK_IN_TASK FOREIGN KEY (student_follow_up_task_id) REFERENCES student_follow_up_tasks (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_check_ins');
        $this->addSql('DROP TABLE student_follow_up_tasks');
        $this->addSql('DROP TABLE student_follow_up_plans');
        $this->addSql('DROP TABLE follow_up_plan_steps');
        $this->addSql('DROP TABLE follow_up_plan_versions');
        $this->addSql('DROP TABLE follow_up_plan_templates');
    }
}
