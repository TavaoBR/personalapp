<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação da tabela refresh_tokens para sessões persistentes e seguras via cookie HttpOnly';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_tokens (
            id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            revoked TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_REFRESH_TOKEN_HASH (token_hash),
            INDEX idx_refresh_token_hash (token_hash),
            INDEX IDX_REFRESH_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_REFRESH_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
    }
}
