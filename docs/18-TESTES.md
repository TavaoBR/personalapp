# 18 — Estratégia de Testes

## Backend `api/`

Ferramenta principal: **PHPUnit**.

Cobrir:

- unit;
- integration;
- repository com MySQL de teste;
- authorization;
- tenancy;
- API;
- migrations;
- handlers do Messenger;
- storage local;
- uploads e downloads protegidos.

## Frontend `app/`

Ferramentas principais:

- Vitest;
- Vue Test Utils.

Cobrir:

- components;
- Pinia stores;
- services;
- forms;
- routes;
- permission-aware UI;
- workout execution;
- analytics rendering.

## E2E

Ferramenta: **Playwright**.

Fluxos críticos:

- login/refresh/logout;
- troca/resolução de tenant quando aplicável;
- cadastro de aluno;
- criação e atribuição de ficha;
- execução de treino;
- avaliação e histórico;
- upload e acesso autorizado a arquivo.

## Cenários principais

### Templates

- personalização não altera template;
- template atualizado não altera instância automaticamente.

### Versionamento

- v1 permanece consultável;
- v2 não altera execução baseada em v1.

### Histórico

- sessão mantém dados usados no momento da execução.

### Analytics

- comparação de períodos;
- zero data;
- períodos incompletos;
- múltiplas sessões no mesmo dia.

### Segurança

Matriz cross-tenant e cross-student obrigatória.

### Infraestrutura econômica do MVP

Também validar:

- restart/redeploy não perde arquivos quando volume persistente está configurado;
- mensagens do Messenger persistem no Doctrine transport;
- falha de handler segue estratégia de retry/failure transport configurada;
- arquivo de outro tenant nunca é retornado.
