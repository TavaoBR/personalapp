# ⚙️ PersonalApp Backend (API) — Symfony 7 & PHP 8.2+

> API RESTful de alta performance, multi-tenant e orientada a domínio para o ecossistema SaaS de Personal Trainers e Assessorias.

---

## 📋 Sumário
- [Arquitetura de Software](#arquitetura-de-software)
- [Multi-Tenancy e Segurança](#multi-tenancy-e-segurança)
- [Estrutura de Diretórios](#estrutura-de-diretórios)
- [Instalação e Execução Local](#instalação-e-execução-local)
- [Banco de Dados e Migrações](#banco-de-dados-e-migrações)
- [Executando Testes Automatizados](#executando-testes-automatizados)
- [Catálogo de Rotas Principais](#catálogo-de-rotas-principais)

---

## 🏛️ Arquitetura de Software

A API adota princípios de **Clean Architecture** e **Domain-Driven Design (DDD) pragmático**:

1. **Domain (`src/Domain/`)**: Entidades puras do Doctrine, Value Objects e Enums de negócio sem dependências de infraestrutura.
2. **Application (`src/Application/`)**: Serviços de caso de uso (`WorkoutTemplateService`, `FinancialService`, `AnalyticsService`, `SaaSBillingService`) e regras de autorização de tenant (`TenantAuthorizationService`).
3. **Infrastructure (`src/Infrastructure/`)**: Repositórios concretos (`StudentProfileRepository`, `WorkoutSessionRepository`), autenticador JWT (`JwtAuthenticator`) e storage de arquivos.
4. **Api / Delivery (`src/Api/Controller/`)**: Controladores HTTP finos que tratam requisições, validam parâmetros e serializam respostas JSON.

---

## 🛡️ Multi-Tenancy e Segurança

- **Resolução de Tenant**: Todas as rotas autenticadas resolvem o tenant através do `TenantResolver`. O cliente envia o cabeçalho `X-Tenant-ID: <uuid>`, que é validado contra as membresias do usuário autenticado no banco.
- **Isolamento de Dados**: Todos os repositórios filtram rigorosamente por `tenant_id`. Nenhuma entidade é criada ou consultada sem associação direta ao tenant ativo.
- **Proteção contra Inadimplência**: Tenants com status `SUSPENDED` têm seu acesso negado automaticamente pelo `TenantResolver` antes de qualquer execução de controller.
- **Autenticação**: Tokens JWT assinados com tempo de expiração curto e Refresh Tokens persistidos com expiração controlada e revogação.

---

## 📁 Estrutura de Diretórios

```text
api/
├── config/                  # Configurações do framework, security, packages e rotas
├── migrations/              # Arquivos de migração versionados do Doctrine
├── public/                  # Ponto de entrada HTTP index.php
├── src/
│   ├── Api/Controller/      # Controllers RESTful organizados por domínio
│   │   ├── AuthController.php
│   │   ├── WorkoutTemplateController.php
│   │   ├── WorkoutSessionController.php
│   │   ├── AssessmentController.php
│   │   ├── AnamnesisController.php
│   │   ├── FinancialController.php
│   │   ├── SaaSBillingController.php
│   │   ├── PlatformAdminController.php
│   │   └── ...
│   ├── Application/         # Casos de uso, orquestração e contexto de tenant
│   │   ├── Security/        # TenantAuthorizationService, Permission
│   │   └── Service/         # WorkoutPlanService, FinancialService, etc.
│   ├── Domain/              # Entidades Doctrine, Enums e Exceções de Domínio
│   │   ├── Entity/          # Tenant, User, Exercise, WorkoutSession, etc.
│   │   └── Enum/            # TenantRole, TenantType, PaymentStatus, etc.
│   └── Infrastructure/      # Implementações técnicas (Repositories, Storage, Security)
├── tests/                   # Suíte de testes automatizados com PHPUnit
├── phpunit.dist.xml         # Configuração de execução de testes (SQLite in-memory)
└── .env.example             # Modelo de variáveis de ambiente sem segredos
```

---

## 🚀 Instalação e Execução Local

### 1. Pré-requisitos
- PHP 8.2 ou superior instalado com as extensões `pdo_mysql`, `pdo_sqlite`, `ctype`, `intl`, `mbstring`.
- Composer 2.x

### 2. Passos de Configuração
```bash
# Instalar dependências
composer install

# Copiar arquivo de ambiente
cp .env.example .env

# Ajustar DATABASE_URL no .env:
# DATABASE_URL="mysql://usuario:senha@127.0.0.1:3306/personalapp_dev?serverVersion=8.0.32&charset=utf8mb4"

# Criar banco e rodar migrações
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

# Iniciar servidor local
php -S 127.0.0.1:8000 -t public
```

---

## 🧪 Executando Testes Automatizados

A suíte de testes utiliza um banco SQLite temporário gerado em tempo de execução (`var/test_test.db`), sem afetar a base de dados de desenvolvimento:

```bash
vendor/bin/phpunit
```

> **Cobertura**: 86 testes e 515 asserções aprovados com 100% de sucesso.

---

## 📡 Catálogo de Rotas Principais

| Método | Endpoint | Descrição | Permissão Mínima |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/auth/login` | Autenticação e emissão de tokens | Público |
| `POST` | `/api/auth/refresh` | Renovação de token JWT | Público |
| `GET` | `/api/me` | Dados do usuário logado e tenants | Autenticado |
| `GET` | `/api/exercises` | Listagem de exercícios com filtros | `VIEW_WORKOUTS` |
| `GET` | `/api/workout-templates` | Fichas e templates de treino | `VIEW_WORKOUTS` |
| `POST` | `/api/workout-sessions` | Registro de treino ao vivo executado | `MANAGE_WORKOUTS` |
| `GET` | `/api/students/{id}/assessments` | Avaliações físicas do aluno | `VIEW_STUDENTS` |
| `GET` | `/api/students/{id}/analytics` | KPIs de evolução e comparativos | `VIEW_STUDENTS` |
| `GET` | `/api/finances/dashboard` | Indicadores de receita e inadimplência | `VIEW_FINANCES` |
| `POST` | `/api/finances/payments` | Emissão de fatura / cobrança | `MANAGE_FINANCES` |
| `POST` | `/api/finances/payments/{id}/pay`| Baixa / quitação de mensalidade | `MANAGE_FINANCES` |
| `GET` | `/api/saas/subscription` | Assinatura atual do espaço | `MANAGE_TENANT` |
| `GET` | `/api/admin/metrics` | Métricas globais da plataforma | Superadmin (`ROLE_PLATFORM_ADMIN`) |
