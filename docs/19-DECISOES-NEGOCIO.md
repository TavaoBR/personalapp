# 19 — Decisões de Negócio

## Template x Instância

Templates são reutilizáveis.
Instâncias dos alunos são independentes.

## Plano de acompanhamento x Ficha

São conceitos diferentes.

Plano de acompanhamento organiza jornada e tarefas.

Ficha de treino define prescrição.

## SaaS Billing x Student Billing

São domínios financeiros diferentes.

## Métricas

Devem ser extensíveis.

## Metodologia

A plataforma oferece blocos configuráveis.

Não deve impor:

- uma única divisão de treino;
- um único tipo de anamnese;
- um único processo de avaliação;
- um único plano de acompanhamento;
- métricas fixas demais.

Cada Personal adapta o sistema à própria metodologia.

## Decisões técnicas do MVP

Por decisão de custo e simplicidade operacional:

- MySQL é o banco relacional oficial;
- não utilizar PostgreSQL neste projeto;
- uploads utilizam filesystem local em volume persistente;
- não utilizar S3 ou Cloudflare R2 no MVP;
- Symfony Messenger utiliza Doctrine transport;
- não utilizar Redis no MVP;
- Docker + Nginx formam a base de execução/deploy;
- serviços devem ser abstraídos para permitir evolução futura sem acoplamento do domínio.
