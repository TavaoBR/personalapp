<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de templates de treino e fichas individualizadas de alunos';
    }

    public function up(Schema $schema): void
    {
        // 1. workout_templates
        $this->addSql('CREATE TABLE workout_templates (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_workout_template_tenant (tenant_id),
            INDEX idx_workout_template_status (tenant_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_TEMPLATE_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. workout_template_versions
        $this->addSql('CREATE TABLE workout_template_versions (
            id VARCHAR(36) NOT NULL,
            workout_template_id VARCHAR(36) NOT NULL,
            version_number INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            objective VARCHAR(80) DEFAULT NULL,
            level VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            published_at DATETIME DEFAULT NULL,
            INDEX idx_template_version_lookup (workout_template_id, version_number),
            INDEX idx_template_version_status (workout_template_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_VER_TEMPLATE FOREIGN KEY (workout_template_id) REFERENCES workout_templates (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. workout_template_days
        $this->addSql('CREATE TABLE workout_template_days (
            id VARCHAR(36) NOT NULL,
            workout_template_version_id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            INDEX idx_template_day_version (workout_template_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_DAY_TEMPLATE_VERSION FOREIGN KEY (workout_template_version_id) REFERENCES workout_template_versions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. workout_template_exercises
        $this->addSql('CREATE TABLE workout_template_exercises (
            id VARCHAR(36) NOT NULL,
            workout_template_day_id VARCHAR(36) NOT NULL,
            exercise_version_id VARCHAR(36) NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            sets INT NOT NULL,
            reps VARCHAR(50) NOT NULL,
            weight VARCHAR(50) DEFAULT NULL,
            rest_seconds INT DEFAULT NULL,
            rpe VARCHAR(20) DEFAULT NULL,
            technique VARCHAR(100) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX idx_template_exercise_day (workout_template_day_id),
            INDEX idx_template_exercise_version (exercise_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_EX_TEMPLATE_DAY FOREIGN KEY (workout_template_day_id) REFERENCES workout_template_days (id) ON DELETE CASCADE,
            CONSTRAINT FK_EX_TEMPLATE_VERSION FOREIGN KEY (exercise_version_id) REFERENCES exercise_versions (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. student_workout_plans
        $this->addSql('CREATE TABLE student_workout_plans (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            source_template_id VARCHAR(36) DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_plan_tenant (tenant_id),
            INDEX idx_student_plan_student (student_profile_id),
            INDEX idx_student_plan_status (student_profile_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_PLAN_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_PLAN_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_PLAN_SOURCE_TEMPLATE FOREIGN KEY (source_template_id) REFERENCES workout_templates (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. student_workout_plan_versions
        $this->addSql('CREATE TABLE student_workout_plan_versions (
            id VARCHAR(36) NOT NULL,
            student_workout_plan_id VARCHAR(36) NOT NULL,
            source_template_version_id VARCHAR(36) DEFAULT NULL,
            version_number INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            valid_from DATETIME DEFAULT NULL,
            valid_until DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_plan_version_lookup (student_workout_plan_id, version_number),
            INDEX idx_plan_version_status (student_workout_plan_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_VER_PLAN FOREIGN KEY (student_workout_plan_id) REFERENCES student_workout_plans (id) ON DELETE CASCADE,
            CONSTRAINT FK_VER_SOURCE_TEMPLATE_VER FOREIGN KEY (source_template_version_id) REFERENCES workout_template_versions (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 7. student_workout_days
        $this->addSql('CREATE TABLE student_workout_days (
            id VARCHAR(36) NOT NULL,
            student_workout_plan_version_id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            INDEX idx_student_day_version (student_workout_plan_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_STUDENT_DAY_PLAN_VER FOREIGN KEY (student_workout_plan_version_id) REFERENCES student_workout_plan_versions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 8. student_workout_exercises
        $this->addSql('CREATE TABLE student_workout_exercises (
            id VARCHAR(36) NOT NULL,
            student_workout_day_id VARCHAR(36) NOT NULL,
            exercise_version_id VARCHAR(36) NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            sets INT NOT NULL,
            reps VARCHAR(50) NOT NULL,
            weight VARCHAR(50) DEFAULT NULL,
            rest_seconds INT DEFAULT NULL,
            rpe VARCHAR(20) DEFAULT NULL,
            technique VARCHAR(100) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX idx_student_exercise_day (student_workout_day_id),
            INDEX idx_student_exercise_version (exercise_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_EX_STUDENT_DAY FOREIGN KEY (student_workout_day_id) REFERENCES student_workout_days (id) ON DELETE CASCADE,
            CONSTRAINT FK_EX_STUDENT_VER FOREIGN KEY (exercise_version_id) REFERENCES exercise_versions (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_workout_exercises');
        $this->addSql('DROP TABLE student_workout_days');
        $this->addSql('DROP TABLE student_workout_plan_versions');
        $this->addSql('DROP TABLE student_workout_plans');
        $this->addSql('DROP TABLE workout_template_exercises');
        $this->addSql('DROP TABLE workout_template_days');
        $this->addSql('DROP TABLE workout_template_versions');
        $this->addSql('DROP TABLE workout_templates');
    }
}
