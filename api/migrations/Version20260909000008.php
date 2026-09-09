<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de anamnese configurável: anamnesis_templates, anamnesis_template_versions, anamnesis_questions, student_anamneses e student_anamnesis_answers';
    }

    public function up(Schema $schema): void
    {
        // 1. anamnesis_templates
        $this->addSql('CREATE TABLE anamnesis_templates (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_anamnesis_template_tenant (tenant_id),
            INDEX idx_anamnesis_template_status (tenant_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_ANAMNESIS_TPL_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. anamnesis_template_versions
        $this->addSql('CREATE TABLE anamnesis_template_versions (
            id VARCHAR(36) NOT NULL,
            anamnesis_template_id VARCHAR(36) NOT NULL,
            version_number INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'PUBLISHED\',
            created_at DATETIME NOT NULL,
            published_at DATETIME DEFAULT NULL,
            INDEX idx_anamnesis_ver_lookup (anamnesis_template_id, version_number),
            PRIMARY KEY(id),
            CONSTRAINT FK_ANAMNESIS_VER_TPL FOREIGN KEY (anamnesis_template_id) REFERENCES anamnesis_templates (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. anamnesis_questions
        $this->addSql('CREATE TABLE anamnesis_questions (
            id VARCHAR(36) NOT NULL,
            template_version_id VARCHAR(36) NOT NULL,
            question_text LONGTEXT NOT NULL,
            question_type VARCHAR(30) NOT NULL,
            required TINYINT(1) NOT NULL DEFAULT 0,
            order_index INT NOT NULL DEFAULT 0,
            configuration JSON DEFAULT NULL,
            INDEX idx_anamnesis_question_version (template_version_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ANAMNESIS_Q_VER FOREIGN KEY (template_version_id) REFERENCES anamnesis_template_versions (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. student_anamneses
        $this->addSql('CREATE TABLE student_anamneses (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            template_version_id VARCHAR(36) NOT NULL,
            answered_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'SUBMITTED\',
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_student_anamnesis_tenant (tenant_id),
            INDEX idx_student_anamnesis_student (student_profile_id),
            INDEX idx_student_anamnesis_date (student_profile_id, answered_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_STUDENT_ANAMNESIS_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_STUDENT_ANAMNESIS_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE,
            CONSTRAINT FK_STUDENT_ANAMNESIS_VER FOREIGN KEY (template_version_id) REFERENCES anamnesis_template_versions (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. student_anamnesis_answers
        $this->addSql('CREATE TABLE student_anamnesis_answers (
            id VARCHAR(36) NOT NULL,
            student_anamnesis_id VARCHAR(36) NOT NULL,
            anamnesis_question_id VARCHAR(36) DEFAULT NULL,
            question_text_snapshot LONGTEXT NOT NULL,
            answer_value LONGTEXT DEFAULT NULL,
            INDEX idx_anamnesis_ans_session (student_anamnesis_id),
            INDEX idx_anamnesis_ans_question (anamnesis_question_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ANAMNESIS_ANS_SESSION FOREIGN KEY (student_anamnesis_id) REFERENCES student_anamneses (id) ON DELETE CASCADE,
            CONSTRAINT FK_ANAMNESIS_ANS_Q FOREIGN KEY (anamnesis_question_id) REFERENCES anamnesis_questions (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_anamnesis_answers');
        $this->addSql('DROP TABLE student_anamneses');
        $this->addSql('DROP TABLE anamnesis_questions');
        $this->addSql('DROP TABLE anamnesis_template_versions');
        $this->addSql('DROP TABLE anamnesis_templates');
    }
}
