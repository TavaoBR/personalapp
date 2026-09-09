# Foundation

## Objetivo

Implementar esta fase sem antecipar funcionalidades de fases posteriores.

## Escopo


- garantir estrutura `api/` e `app/`;
- inicializar Symfony 7 + PHP 8.4 em `api/`;
- inicializar Vue 3 + TypeScript + Pinia + Tailwind CSS + Flowbite em `app/`;
- configurar MySQL e Doctrine ORM;
- configurar Doctrine Migrations;
- configurar Symfony Messenger com Doctrine transport;
- preparar `FileStorageInterface` + `LocalFileStorage`;
- configurar volume persistente para uploads;
- configurar Docker e Nginx;
- comunicação app → api;
- health checks;
- configuração de ambiente sem secrets versionados.


## Pré-requisitos

- ler `docs/00-IA-INICIALIZACAO.md`;
- ler documentos de domínio relacionados;
- inspecionar `api/`;
- inspecionar `app/`;
- verificar tasks anteriores concluídas.

## Antes de implementar

A IA deve informar:

```text
Estado atual
Arquivos relacionados
Gaps
Arquivos a criar
Arquivos a alterar
Migrations
Endpoints
Testes
Riscos
```

## Stack obrigatória desta task

```text
Vue 3 + TypeScript + Pinia + Tailwind + Flowbite
Symfony 7 + PHP 8.4 + Doctrine
MySQL
Symfony Messenger + Doctrine transport
Filesystem local persistente
Docker + Nginx
```

Não adicionar Redis, S3 ou R2 nesta fase.

## Regras

- backend somente em `api/`;
- frontend somente em `app/`;
- respeitar TenantContext;
- não confiar em IDs de escopo enviados pelo frontend;
- preservar histórico;
- criar migrations;
- criar testes;
- atualizar docs se decisões mudarem.

## Critérios de aceite

- funcionalidade implementada;
- authorization aplicada;
- isolamento tenant validado;
- cenários negativos testados;
- migrations aplicáveis;
- testes passando;
- documentação consistente.

## Saída obrigatória

```text
Arquivos criados
Arquivos alterados
Migrations
Endpoints
Testes adicionados
Resultado dos testes
Critérios de aceite
Pendências
```
