# 03 — Multitenancy e Permissões

## Tenant

Representa:

```text
PERSONAL
ou
ASSESSORIA
```

## User

Identidade global.

## TenantMembership

Relaciona User ao Tenant.

Roles iniciais:

```text
OWNER
ADMIN
MANAGER
TRAINER
ASSISTANT
FINANCE
STUDENT
```

## TrainerStudentAssignment

Controla quais alunos um Personal pode acompanhar em uma assessoria.

```text
TrainerStudentAssignment
├── TenantId
├── TrainerId
├── StudentId
├── IsPrimary
├── StartedAt
├── EndedAt
└── Status
```

## Permissões

### STUDENT

Somente próprios dados.

### TRAINER

Alunos vinculados a ele.

### MANAGER / ADMIN

Pode receber acesso a todos os alunos do tenant.

### OWNER

Controle completo do tenant.

### PLATFORM_ADMIN

Administração do SaaS, separada do tenant.

## Isolamento

Dois níveis:

```text
Personal A
X
Personal B
```

e:

```text
Student A
X
Student B
```

Cross-tenant ou cross-student deve retornar preferencialmente `404`.
