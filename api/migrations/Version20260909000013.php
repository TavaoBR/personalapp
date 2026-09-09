<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909000013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Criação das tabelas de SaaS Billing: features, saas_plans, plan_features e tenant_subscriptions';
    }

    public function up(Schema $schema): void
    {
        // 1. features
        $this->addSql('CREATE TABLE features (
            id VARCHAR(36) NOT NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_FEATURE_CODE (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. saas_plans
        $this->addSql('CREATE TABLE saas_plans (
            id VARCHAR(36) NOT NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            price_monthly DOUBLE PRECISION NOT NULL,
            price_yearly DOUBLE PRECISION NOT NULL,
            max_students INT DEFAULT NULL,
            max_trainers INT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'ACTIVE\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_SAAS_PLAN_CODE (code),
            INDEX idx_saas_plan_status (status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. plan_features
        $this->addSql('CREATE TABLE plan_features (
            id VARCHAR(36) NOT NULL,
            saas_plan_id VARCHAR(36) NOT NULL,
            feature_id VARCHAR(36) NOT NULL,
            limit_value INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_plan_feature_plan (saas_plan_id),
            INDEX idx_plan_feature_feature (feature_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PLAN_FEATURE_PLAN FOREIGN KEY (saas_plan_id) REFERENCES saas_plans (id) ON DELETE CASCADE,
            CONSTRAINT FK_PLAN_FEATURE_FEATURE FOREIGN KEY (feature_id) REFERENCES features (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. tenant_subscriptions
        $this->addSql('CREATE TABLE tenant_subscriptions (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            saas_plan_id VARCHAR(36) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT \'ACTIVE\',
            started_at DATETIME NOT NULL,
            current_period_start DATETIME NOT NULL,
            current_period_end DATETIME NOT NULL,
            cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
            canceled_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_subscription_tenant (tenant_id),
            INDEX idx_subscription_status (tenant_id, status),
            INDEX idx_subscription_period_end (current_period_end),
            PRIMARY KEY(id),
            CONSTRAINT FK_SUB_TENANT FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT FK_SUB_PLAN FOREIGN KEY (saas_plan_id) REFERENCES saas_plans (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tenant_subscriptions');
        $this->addSql('DROP TABLE plan_features');
        $this->addSql('DROP TABLE saas_plans');
        $this->addSql('DROP TABLE features');
    }
}
