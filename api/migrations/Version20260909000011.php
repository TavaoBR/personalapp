<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação da tabela de timeline unificada do aluno: student_timeline_events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE student_timeline_events (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            student_profile_id VARCHAR(36) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            occurred_at DATETIME NOT NULL,
            entity_type VARCHAR(60) DEFAULT NULL,
            entity_id VARCHAR(36) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_timeline_tenant (tenant_id),
            INDEX idx_timeline_student (student_profile_id),
            INDEX idx_timeline_student_occurred (student_profile_id, occurred_at),
            INDEX idx_timeline_event_type (student_profile_id, event_type),
            PRIMARY KEY(id),
            CONSTRAINT FK_TIMELINE_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_TIMELINE_STUDENT FOREIGN KEY (student_profile_id) REFERENCES student_profiles (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE student_timeline_events');
    }
}
