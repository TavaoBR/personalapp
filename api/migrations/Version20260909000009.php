<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de metas e progresso: student_goals e goal_progress';
    }

    public function up(Schema $schema): void
    {
        // 1. student_goals
        $this->addSql('CREATE TABLE student_goals (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            trainer_profile_id VARCHAR(36) DEFAULT NULL,
            metric_definition_id VARCHAR(36) DEFAULT NULL,
            title VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            initial_value DOUBLE PRECISION DEFAULT NULL,
            target_value DOUBLE PRECISION DEFAULT NULL,
            current_value DOUBLE PRECISION DEFAULT NULL,
            unit VARCHAR(30) DEFAULT NULL,
            start_date DATE NOT NULL,
            target_date DATE DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'IN_PROGRESS\',
            achieved_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_student_goals_tenant (tenant_id),
            INDEX idx_student_goals_student (student_profile_id),
            INDEX idx_student_goals_status (student_profile_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_GOAL_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_GOAL_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_GOAL_TRAINER FOREIGN KEY (trainer_profile_id) REFERENCES trainer_profiles (id) ON DELETE SET NULL,
            CONSTRAINT FK_GOAL_METRIC FOREIGN KEY (metric_definition_id) REFERENCES metric_definitions (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. goal_progress
        $this->addSql('CREATE TABLE goal_progress (
            id VARCHAR(36) NOT NULL,
            student_goal_id VARCHAR(36) NOT NULL,
            recorded_at DATETIME NOT NULL,
            value DOUBLE PRECISION DEFAULT NULL,
            previous_value DOUBLE PRECISION DEFAULT NULL,
            source VARCHAR(30) NOT NULL DEFAULT \'MANUAL\',
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_goal_progress_goal (student_goal_id),
            INDEX idx_goal_progress_date (student_goal_id, recorded_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_PROGRESS_GOAL FOREIGN KEY (student_goal_id) REFERENCES student_goals (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE goal_progress');
        $this->addSql('DROP TABLE student_goals');
    }
}
