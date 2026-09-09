# Personal Trainer SaaS — Planejamento e Documentação

Este diretório concentra a documentação funcional, arquitetural, planejamento de implementação e instruções para IA do SaaS para **Personal Trainers e Assessorias**.

## Estrutura obrigatória do projeto

```text
/
├── api/                    # Backend/API
├── app/                    # Frontend
├── docs/                   # Documentação + planejamento + tasks
├── README.md
└── AI-INSTRUCTIONS.md
```

### `api/`

Todo o backend deve permanecer dentro de `api/`.

Responsabilidades:

- autenticação;
- autorização;
- tenant context;
- regras de negócio;
- repositories;
- banco de dados;
- migrations;
- jobs;
- storage;
- cache;
- auditoria;
- API HTTP;
- testes backend.

### `app/`

Todo o frontend deve permanecer dentro de `app/`.

Responsabilidades:

- autenticação visual;
- navegação;
- dashboards;
- área do Personal;
- área do Aluno;
- execução de treino;
- construtores;
- gráficos;
- formulários;
- stores/state;
- consumo da API;
- testes frontend.

### `docs/`

Contém a fonte de verdade de arquitetura e planejamento.

A IA deve consultar `docs/00-IA-INICIALIZACAO.md` antes de implementar qualquer task.

## Stack oficial do projeto

A stack está definida e deve ser preservada durante a implementação:

### Frontend (`app/`)

- Vue 3;
- TypeScript;
- Pinia;
- Tailwind CSS;
- Flowbite.

### Backend (`api/`)

- Symfony 7;
- PHP 8.4;
- Doctrine ORM;
- MySQL;
- Symfony Messenger com transport Doctrine no MVP.

### Infraestrutura do MVP

- Docker;
- Nginx;
- filesystem local em volume persistente para uploads;
- sem S3/Cloudflare R2 no MVP;
- sem Redis no MVP;
- cache somente quando necessário, usando recursos locais compatíveis com a infraestrutura atual.

> A aplicação deve esconder detalhes de storage e fila atrás de abstrações para permitir migração futura para S3/R2 e Redis sem reescrever as regras de negócio.

Para detalhes e decisões de infraestrutura, consultar `docs/20-STACK-TECNOLOGIA-INFRA.md`.

## Produto

```text
Plataforma SaaS
       ↓
Personal / Assessoria
       ↓
Personais / Equipe
       ↓
Alunos
       ↓
Treinos
Avaliações
Anamneses
Metas
Acompanhamento
Evolução
Histórico
Financeiro
```

## Regra arquitetural central

> Nenhuma operação tenant-aware pode consultar ou alterar dados sem um contexto de tenant válido, e nenhum aluno pode acessar dados pertencentes a outro aluno.

## Planejamento

As tasks estão em:

```text
docs/tasks/
```

Execute uma fase por vez e não avance enquanto os critérios de aceite e testes da fase atual não estiverem concluídos.
