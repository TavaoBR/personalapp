# 15 — Organização do Backend

Raiz obrigatória:

```text
api/
```

## Stack

```text
Symfony 7
PHP 8.4
Doctrine ORM
Doctrine Migrations
MySQL
Symfony Messenger + Doctrine transport
```

## Separação conceitual

```text
Domain
Application
Infrastructure
Api/HTTP
```

### Domain

Entidades, value objects e regras centrais que não dependem diretamente de HTTP ou infraestrutura.

### Application

Casos de uso, services, commands/queries e orchestration.

### Infrastructure

- repositories Doctrine;
- mapeamentos ORM;
- storage;
- mensageria;
- cache quando necessário;
- integrações externas.

### API/HTTP

- controllers;
- DTOs;
- validators;
- autenticação;
- authorization/policies;
- normalização de respostas.

## Regra de Repository

Consultas ao MySQL devem passar por repositories/query services.

Evitar query complexa em controller ou service de apresentação.

## Regra de Controller

```text
request
↓
validation/DTO
↓
application service
↓
response
```

Controller não deve concentrar regra de negócio.

## Mensageria

No MVP, Symfony Messenger usa transport Doctrine no MySQL.

A regra de negócio não deve depender do transport. Handlers recebem mensagens independentes da infraestrutura, permitindo migração futura para Redis/RabbitMQ sem reescrever o domínio.

## Storage

Criar abstração equivalente a:

```php
interface FileStorageInterface
{
    public function store(/* ... */): StoredFile;
    public function delete(string $path): void;
    public function exists(string $path): bool;
}
```

Implementação inicial:

```text
FileStorageInterface
        ↓
LocalFileStorage
        ↓
volume persistente
```

S3/R2 não fazem parte do MVP. Uma implementação remota poderá ser criada posteriormente sem alterar services de domínio.
