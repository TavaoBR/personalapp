<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação da tabela platform_audit_logs para auditoria administrativa da plataforma SaaS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE platform_audit_logs (
            id VARCHAR(36) NOT NULL,
            admin_user_id VARCHAR(36) NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(100) NOT NULL,
            target_id VARCHAR(36) NOT NULL,
            details JSON DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_audit_admin (admin_user_id),
            INDEX idx_audit_action (action),
            INDEX idx_audit_created (created_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_AUDIT_ADMIN_USER FOREIGN KEY (admin_user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE platform_audit_logs');
    }
}
