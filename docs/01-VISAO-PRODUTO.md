# 01 — Visão do Produto

## Objetivo

Criar uma plataforma SaaS completa para Personais e Assessorias gerenciarem a jornada de seus alunos.

## Personal

O Personal deve conseguir:

- cadastrar alunos;
- criar e reutilizar fichas;
- acompanhar treinos executados;
- analisar evolução;
- criar anamneses;
- criar avaliações;
- definir metas;
- acompanhar check-ins;
- manter timeline;
- gerenciar histórico;
- organizar acompanhamento;
- gerenciar seu financeiro com alunos.

## Aluno

O Aluno deve conseguir:

- visualizar ficha atual;
- acessar fichas anteriores;
- executar treino;
- registrar séries;
- registrar repetições;
- registrar carga;
- registrar RPE;
- acompanhar descanso;
- consultar histórico;
- visualizar métricas;
- acompanhar metas;
- comparar evolução;
- acompanhar avaliações e anamnese quando liberadas.

## Assessoria

O domínio deve permitir:

```text
Assessoria
├── Owner
├── Manager
├── Trainer A
├── Trainer B
└── Students
```

sem redesenhar o sistema no futuro.
