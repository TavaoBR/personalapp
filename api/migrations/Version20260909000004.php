<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de exercícios: global_exercises, tenant_exercises e exercise_versions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE global_exercises (
            id VARCHAR(36) NOT NULL,
            name VARCHAR(150) NOT NULL,
            muscle_group VARCHAR(30) NOT NULL,
            equipment VARCHAR(80) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            video_url VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_global_exercise_muscle (muscle_group),
            INDEX idx_global_exercise_name (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE tenant_exercises (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            global_exercise_id VARCHAR(36) DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            muscle_group VARCHAR(30) NOT NULL,
            equipment VARCHAR(80) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            video_url VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_tenant_exercise_tenant (tenant_id),
            INDEX idx_tenant_exercise_muscle (tenant_id, muscle_group),
            PRIMARY KEY(id),
            CONSTRAINT FK_TENANT_EX_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_TENANT_EX_GLOBAL FOREIGN KEY (global_exercise_id) REFERENCES global_exercises (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE exercise_versions (
            id VARCHAR(36) NOT NULL,
            global_exercise_id VARCHAR(36) DEFAULT NULL,
            tenant_exercise_id VARCHAR(36) DEFAULT NULL,
            version_number INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            muscle_group VARCHAR(30) NOT NULL,
            equipment VARCHAR(80) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            video_url VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_exercise_version_tenant_ex (tenant_exercise_id, version_number),
            INDEX idx_exercise_version_global_ex (global_exercise_id, version_number),
            PRIMARY KEY(id),
            CONSTRAINT FK_EX_VER_GLOBAL FOREIGN KEY (global_exercise_id) REFERENCES global_exercises (id) ON DELETE SET NULL,
            CONSTRAINT FK_EX_VER_TENANT FOREIGN KEY (tenant_exercise_id) REFERENCES tenant_exercises (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exercise_versions');
        $this->addSql('DROP TABLE tenant_exercises');
        $this->addSql('DROP TABLE global_exercises');
    }
}
