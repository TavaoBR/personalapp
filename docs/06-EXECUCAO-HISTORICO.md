# 06 — Execução e Histórico

## WorkoutSession

Representa treino realizado.

Campos:

- TenantId;
- StudentId;
- WorkoutPlanId;
- WorkoutPlanVersionId;
- WorkoutDayId;
- StartedAt;
- FinishedAt;
- DurationSeconds;
- Status;
- CompletionPercentage.

## WorkoutSetExecution

Registrar por série:

- repetições prescritas;
- repetições realizadas;
- carga prescrita;
- carga realizada;
- RPE;
- descanso;
- status;
- observação.

## Histórico

Filtros:

- dia;
- semana;
- mês;
- 30 dias;
- 60 dias;
- 90 dias;
- período personalizado.

## Integridade histórica

A sessão deve apontar para a versão exata da ficha usada.

Mudanças futuras na ficha não alteram sessões antigas.
