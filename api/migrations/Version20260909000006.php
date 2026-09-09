<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de execução e histórico de treinos: workout_sessions, workout_exercise_executions e workout_set_executions';
    }

    public function up(Schema $schema): void
    {
        // 1. workout_sessions
        $this->addSql('CREATE TABLE workout_sessions (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            workout_plan_id VARCHAR(36) DEFAULT NULL,
            workout_plan_version_id VARCHAR(36) DEFAULT NULL,
            workout_day_id VARCHAR(36) DEFAULT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            duration_seconds INT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            completion_percentage DOUBLE PRECISION NOT NULL DEFAULT 0.0,
            feedback_notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_session_tenant (tenant_id),
            INDEX idx_session_student (student_profile_id),
            INDEX idx_session_status (tenant_id, status),
            INDEX idx_session_started_at (student_profile_id, started_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_SESSION_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_SESSION_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_SESSION_PLAN FOREIGN KEY (workout_plan_id) REFERENCES student_workout_plans (id) ON DELETE SET NULL,
            CONSTRAINT FK_SESSION_PLAN_VER FOREIGN KEY (workout_plan_version_id) REFERENCES student_workout_plan_versions (id) ON DELETE SET NULL,
            CONSTRAINT FK_SESSION_DAY FOREIGN KEY (workout_day_id) REFERENCES student_workout_days (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. workout_exercise_executions
        $this->addSql('CREATE TABLE workout_exercise_executions (
            id VARCHAR(36) NOT NULL,
            workout_session_id VARCHAR(36) NOT NULL,
            student_workout_exercise_id VARCHAR(36) DEFAULT NULL,
            exercise_version_id VARCHAR(36) NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            notes LONGTEXT DEFAULT NULL,
            INDEX idx_exercise_exec_session (workout_session_id),
            INDEX idx_exercise_exec_version (exercise_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_EXEC_SESSION FOREIGN KEY (workout_session_id) REFERENCES workout_sessions (id) ON DELETE CASCADE,
            CONSTRAINT FK_EXEC_STUDENT_EX FOREIGN KEY (student_workout_exercise_id) REFERENCES student_workout_exercises (id) ON DELETE SET NULL,
            CONSTRAINT FK_EXEC_EX_VER FOREIGN KEY (exercise_version_id) REFERENCES exercise_versions (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. workout_set_executions
        $this->addSql('CREATE TABLE workout_set_executions (
            id VARCHAR(36) NOT NULL,
            exercise_execution_id VARCHAR(36) NOT NULL,
            set_number INT NOT NULL,
            reps_prescribed VARCHAR(50) DEFAULT NULL,
            reps_performed INT DEFAULT NULL,
            weight_prescribed VARCHAR(50) DEFAULT NULL,
            weight_performed DOUBLE PRECISION DEFAULT NULL,
            rest_seconds INT DEFAULT NULL,
            rpe DOUBLE PRECISION DEFAULT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            notes LONGTEXT DEFAULT NULL,
            INDEX idx_set_exec_exercise (exercise_execution_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_SET_EXERCISE_EXEC FOREIGN KEY (exercise_execution_id) REFERENCES workout_exercise_executions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workout_set_executions');
        $this->addSql('DROP TABLE workout_exercise_executions');
        $this->addSql('DROP TABLE workout_sessions');
    }
}
