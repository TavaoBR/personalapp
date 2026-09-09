<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de avaliações físicas e métricas corporais: metric_definitions, physical_assessments, assessment_metrics e student_metric_records';
    }

    public function up(Schema $schema): void
    {
        // 1. metric_definitions
        $this->addSql('CREATE TABLE metric_definitions (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) NOT NULL,
            unit VARCHAR(20) NOT NULL,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_metric_def_tenant (tenant_id),
            INDEX idx_metric_def_code (code),
            PRIMARY KEY(id),
            CONSTRAINT FK_METRIC_DEF_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. physical_assessments
        $this->addSql('CREATE TABLE physical_assessments (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            trainer_profile_id VARCHAR(36) DEFAULT NULL,
            date DATE NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'COMPLETED\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_assessment_tenant (tenant_id),
            INDEX idx_assessment_student (student_profile_id),
            INDEX idx_assessment_date (student_profile_id, date),
            PRIMARY KEY(id),
            CONSTRAINT FK_ASSESS_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_ASSESS_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_ASSESS_TRAINER FOREIGN KEY (trainer_profile_id) REFERENCES trainer_profiles (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. assessment_metrics
        $this->addSql('CREATE TABLE assessment_metrics (
            id VARCHAR(36) NOT NULL,
            physical_assessment_id VARCHAR(36) NOT NULL,
            metric_definition_id VARCHAR(36) NOT NULL,
            numeric_value DOUBLE PRECISION DEFAULT NULL,
            text_value VARCHAR(150) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX idx_assessment_metric_assessment (physical_assessment_id),
            INDEX idx_assessment_metric_definition (metric_definition_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ASSESS_METRIC_PARENT FOREIGN KEY (physical_assessment_id) REFERENCES physical_assessments (id) ON DELETE CASCADE,
            CONSTRAINT FK_ASSESS_METRIC_DEF FOREIGN KEY (metric_definition_id) REFERENCES metric_definitions (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. student_metric_records
        $this->addSql('CREATE TABLE student_metric_records (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            metric_definition_id VARCHAR(36) NOT NULL,
            physical_assessment_id VARCHAR(36) DEFAULT NULL,
            measured_at DATETIME NOT NULL,
            numeric_value DOUBLE PRECISION DEFAULT NULL,
            text_value VARCHAR(150) DEFAULT NULL,
            source VARCHAR(30) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_metric_rec_tenant (tenant_id),
            INDEX idx_metric_rec_student_date (student_profile_id, measured_at),
            INDEX idx_metric_rec_student_def (student_profile_id, metric_definition_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_METRIC_REC_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_METRIC_REC_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_METRIC_REC_DEF FOREIGN KEY (metric_definition_id) REFERENCES metric_definitions (id) ON DELETE RESTRICT,
            CONSTRAINT FK_METRIC_REC_ASSESS FOREIGN KEY (physical_assessment_id) REFERENCES physical_assessments (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_metric_records');
        $this->addSql('DROP TABLE assessment_metrics');
        $this->addSql('DROP TABLE physical_assessments');
        $this->addSql('DROP TABLE metric_definitions');
    }
}
