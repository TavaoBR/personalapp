# 20 — Stack, Tecnologia e Infraestrutura

Este documento é a fonte de verdade para a stack técnica do projeto.

## 1. Stack oficial

### Frontend

```text
Vue 3
TypeScript
Pinia
Tailwind CSS
Flowbite
```

Responsabilidades principais:

- SPA;
- área administrativa do Personal/Assessoria;
- área do aluno;
- formulários e builders;
- dashboards e analytics;
- execução de treino;
- consumo da API REST.

### Backend

```text
Symfony 7
PHP 8.4
Doctrine ORM
Doctrine Migrations
Symfony Messenger
```

Responsabilidades principais:

- autenticação;
- autorização;
- TenantContext;
- regras de negócio;
- versionamento;
- persistência;
- jobs;
- storage;
- API REST;
- auditoria.

### Banco de dados

```text
MySQL
```

MySQL é a única fonte relacional de verdade do MVP.

Doctrine ORM será usado para persistência e Doctrine Migrations para evolução do schema.

## 2. Arquitetura econômica do MVP

Para reduzir custo e complexidade operacional, o MVP não utiliza:

```text
S3
Cloudflare R2
Redis
Elasticsearch
Kubernetes
```

A ausência desses serviços é uma decisão arquitetural intencional, não uma pendência.

## 3. Uploads e arquivos

### Estratégia inicial

```text
Application Service
       ↓
FileStorageInterface
       ↓
LocalFileStorage
       ↓
Volume persistente
```

Os bytes dos arquivos ficam no filesystem. O MySQL armazena metadados.

Estrutura conceitual:

```text
/storage
└── tenants
    └── {tenant-uuid}
        ├── students
        │   └── {student-uuid}
        │       ├── profile
        │       ├── assessments
        │       └── progress
        ├── exercises
        └── documents
```

A estrutura física pode evoluir, mas deve sempre manter isolamento lógico de tenant.

### Regras

- storage fora do web root;
- nomes físicos gerados pelo servidor;
- validação de MIME type, extensão e tamanho;
- metadados com `tenant_id`;
- acesso a arquivo privado somente após authorization;
- caminhos absolutos nunca retornados pela API;
- volume obrigatório em produção;
- backups devem incluir banco e filesystem.

## 4. Filas e jobs

No MVP:

```text
Symfony Messenger
       ↓
Doctrine Transport
       ↓
MySQL
```

Usos esperados:

- notificações;
- geração de relatórios;
- processamento não crítico assíncrono;
- tarefas agendadas;
- eventos de timeline/analytics que possam ser processados fora da request.

A aplicação deve manter handlers independentes do transport para permitir futura migração para Redis/RabbitMQ.

## 5. Cache

Redis não será utilizado inicialmente.

Regras:

- não adicionar cache sem necessidade medida;
- para cache simples, usar mecanismo local suportado pelo Symfony;
- nunca usar cache sem chave tenant-aware quando o dado for isolado por tenant;
- dados de autorização e ownership não podem depender exclusivamente de cache.

## 6. Infraestrutura

```text
Internet
   ↓
Nginx
├── Vue SPA
└── Symfony API
      ├── MySQL
      ├── Messenger/Doctrine
      └── LocalFileStorage
             ↓
       Volume persistente
```

### Docker

Containers esperados conceitualmente:

```text
nginx
app/frontend build or static files
api/php
mysql
messenger-worker
```

O worker pode compartilhar a mesma imagem da API com comando/processo diferente.

### Volumes

Obrigatórios em produção:

```text
mysql_data
app_storage
```

Nunca depender do filesystem efêmero do container para uploads.

## 7. Deploy

O projeto pode ser implantado em qualquer provedor que suporte:

- containers ou runtime PHP adequado;
- MySQL;
- volume persistente;
- processo de worker;
- HTTPS.

Se Railway ou outro PaaS for utilizado, a configuração deve garantir persistência real do volume de uploads.

## 8. Autenticação

Estratégia esperada:

- access token de curta duração;
- refresh token revogável;
- refresh token em cookie `HttpOnly`;
- `Secure` obrigatório em produção;
- `SameSite` configurado conforme topologia frontend/API;
- expiração deve invalidar a sessão visual e exigir renovação ou novo login conforme política.

A definição final dos bundles/libs Symfony deve respeitar a implementação escolhida, mas não pode reduzir os requisitos de segurança.

## 9. Testes

### Backend

```text
PHPUnit
```

### Frontend

```text
Vitest
Vue Test Utils
```

### E2E

```text
Playwright
```

## 10. Evolução futura

Somente quando houver justificativa:

```text
LocalFileStorage → S3FileStorage / R2FileStorage
Doctrine Messenger → Redis/RabbitMQ transport
Local cache → Redis cache
```

A migração deve ocorrer pela troca de adapters/infraestrutura, sem alterar regras de negócio ou contratos centrais.

## 11. Regra para IA e desenvolvedores

Não substituir a stack oficial por preferência.

Qualquer proposta de mudança de banco, framework, storage, fila ou infraestrutura exige:

1. justificativa;
2. impacto;
3. plano de migração;
4. atualização desta documentação;
5. aprovação explícita antes da implementação.
