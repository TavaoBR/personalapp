# AI-INSTRUCTIONS — Regras obrigatórias para IA

Antes de modificar qualquer arquivo deste projeto:

1. Leia `README.md`.
2. Leia `docs/00-IA-INICIALIZACAO.md`.
3. Leia `docs/01-VISAO-PRODUTO.md`.
4. Leia `docs/02-ARQUITETURA.md`.
5. Leia `docs/20-STACK-TECNOLOGIA-INFRA.md`.
6. Leia os documentos de domínio relacionados à task.
7. Leia a task correspondente em `docs/tasks/`.
8. Inspecione o código real existente em `api/` e `app/`.
9. Só depois proponha alterações.

## Stack obrigatória

A IA não deve substituir a stack por preferência pessoal. A stack oficial é:

```text
Frontend: Vue 3 + TypeScript + Pinia + Tailwind CSS + Flowbite
Backend: Symfony 7 + PHP 8.4 + Doctrine ORM
Banco: MySQL
Fila: Symfony Messenger + Doctrine transport
Uploads: filesystem local persistente
Infra: Docker + Nginx
```

S3, Cloudflare R2 e Redis não fazem parte do MVP. Só devem ser introduzidos após decisão arquitetural explícita e atualização da documentação.

## Diretórios obrigatórios

```text
api/ = backend
app/ = frontend
```

Não criar backend ou frontend fora dessas pastas.

## Antes de implementar

A IA deve apresentar:

```text
1. Estado atual encontrado
2. Arquivos existentes relacionados
3. Problemas identificados
4. Arquivos que serão criados
5. Arquivos que serão alterados
6. Migrations necessárias
7. Endpoints envolvidos
8. Testes que serão criados/alterados
9. Riscos
```

## Durante a implementação

- preservar arquitetura existente quando ela for adequada;
- não inventar arquivos que já existam;
- não duplicar services/repositories/components;
- controllers devem ser finas;
- consultas ao banco devem ficar em repositories/query services;
- regras de negócio ficam em services/application layer;
- alterações de banco exigem migration;
- não armazenar secrets no repositório;
- não confiar em `TenantId`, `StudentId`, roles ou permissions vindos do frontend;
- frontend não é camada de segurança;
- templates nunca devem ser modificados por personalização individual de aluno;
- versões publicadas devem preservar histórico;
- execuções históricas não podem mudar porque uma ficha atual mudou;
- toda task deve incluir testes.

## Ao finalizar

A IA deve informar:

```text
Arquivos criados
Arquivos alterados
Migrations
Endpoints
Testes
Resultado dos testes
Critérios de aceite validados
Pendências
```

Não avançar automaticamente para outra fase.
