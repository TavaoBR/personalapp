# Produção

## Objetivo

Implementar esta fase sem antecipar funcionalidades de fases posteriores.

## Escopo


- hardening do storage local tenant-aware;
- volume persistente para uploads;
- política de backup do MySQL e dos uploads;
- cache local somente quando necessário e tenant-safe;
- jobs via Symfony Messenger + Doctrine transport;
- retry/failure transport;
- logs;
- métricas;
- rate limiting;
- validação de restore de backup.


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

## Restrições do MVP

- não introduzir Redis sem decisão arquitetural explícita;
- não introduzir S3/R2 sem decisão arquitetural explícita;
- não depender de filesystem efêmero do container;
- todo deploy deve montar volume persistente para uploads.

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
