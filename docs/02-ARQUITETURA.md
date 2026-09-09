# 02 — Arquitetura

## Visão geral

```text
Browser
  ↓ HTTPS
Nginx
  ├── app/ → Vue 3 + TypeScript
  └── /api → Symfony 7 + PHP 8.4
                 ↓
          Authentication
                 ↓
          Tenant Resolution
                 ↓
            TenantContext
                 ↓
           Authorization
                 ↓
       Application Services
          ↙             ↘
   Repositories        Messenger
        ↓                 ↓
      MySQL      Doctrine transport/MySQL

Uploads
  ↓
FileStorageInterface
  ↓
LocalFileStorage
  ↓
Volume persistente fora do web root
```

## Stack oficial

### Frontend

- Vue 3;
- TypeScript;
- Pinia;
- Tailwind CSS;
- Flowbite.

### Backend

- Symfony 7;
- PHP 8.4;
- Doctrine ORM;
- Symfony Messenger;
- API REST.

### Persistência

- MySQL como banco relacional principal;
- Doctrine Migrations para qualquer alteração de schema;
- transport Doctrine do Messenger no MVP.

### Infraestrutura

- Docker;
- Nginx;
- volume persistente para MySQL;
- volume persistente para arquivos enviados pelos usuários.

## Decisões de custo do MVP

Não fazem parte da arquitetura inicial:

```text
S3
Cloudflare R2
Redis
Elasticsearch
Kubernetes
```

Esses componentes podem ser adotados futuramente somente quando houver necessidade técnica ou financeira justificável.

## Organização do backend

Dentro de `api/`:

```text
api/
├── config/
├── migrations/
├── public/
├── src/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   ├── Storage/
│   │   └── Messaging/
│   └── Api/
├── tests/
└── var/
```

Controllers devem permanecer finas. Queries ficam em repositories/query services e regras de negócio em services/application layer.

## Organização do frontend

Dentro de `app/`:

```text
app/
└── src/
    ├── modules/
    │   ├── auth/
    │   ├── students/
    │   ├── workouts/
    │   ├── exercises/
    │   ├── assessments/
    │   ├── anamnesis/
    │   ├── goals/
    │   ├── follow-up/
    │   ├── analytics/
    │   └── settings/
    ├── shared/
    ├── router/
    └── stores/
```

## Storage local

Arquivos não devem ser gravados no banco como BLOB por padrão. O MySQL guarda apenas metadados e referências.

Exemplo conceitual:

```text
TenantFile
- id
- tenant_id
- owner_type
- owner_id
- original_name
- stored_name
- relative_path
- mime_type
- size_bytes
- created_at
```

O arquivo físico fica em volume persistente e fora de `public/`. A entrega de arquivo privado deve passar por autorização da API.

## Princípios

- organização por domínio;
- baixo acoplamento;
- backend como autoridade;
- histórico imutável quando publicado;
- templates reutilizáveis;
- instâncias independentes;
- analytics desacoplado das controllers;
- abstrair storage e mensageria;
- não acoplar regra de negócio ao filesystem local ou ao transport Doctrine.
