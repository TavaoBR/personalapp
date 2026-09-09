# 04 — Modelo de Dados

## Identidade

```text
User
Tenant
TenantMembership
TrainerProfile
StudentProfile
TrainerStudentAssignment
```

## Exercícios

```text
GlobalExercise
TenantExercise
ExerciseVersion
```

## Treinos

```text
WorkoutTemplate
WorkoutTemplateVersion
WorkoutTemplateDay
WorkoutTemplateExercise

StudentWorkoutPlan
StudentWorkoutPlanVersion
StudentWorkoutDay
StudentWorkoutExercise

WorkoutSession
WorkoutExerciseExecution
WorkoutSetExecution
```

## Anamnese

```text
AnamnesisTemplate
AnamnesisTemplateVersion
AnamnesisQuestion
StudentAnamnesis
StudentAnamnesisAnswer
```

## Evolução

```text
MetricDefinition
PhysicalAssessment
AssessmentMetric
StudentMetricRecord
```

## Metas

```text
StudentGoal
GoalProgress
```

## Acompanhamento

```text
FollowUpPlanTemplate
FollowUpPlanVersion
FollowUpPlanStep

StudentFollowUpPlan
StudentFollowUpTask

StudentCheckIn
StudentNote
StudentTimelineEvent
```

## Financeiro

Separar:

```text
SaaSPlan
TenantSubscription
```

de:

```text
CoachingPlan
StudentEnrollment
StudentPayment
```

## Convenções de persistência — MySQL

O banco oficial do projeto é **MySQL**.

Regras:

- schema gerenciado por Doctrine Migrations;
- foreign keys explícitas;
- índices em chaves de tenant, relacionamentos e filtros frequentes;
- `tenant_id` obrigatório em entidades tenant-aware;
- timestamps armazenados de forma consistente e convertidos na borda quando necessário;
- não armazenar uploads grandes como BLOB por padrão;
- metadados de arquivos ficam no MySQL e os bytes ficam no filesystem persistente;
- queries devem ser analisadas para evitar N+1 e scans desnecessários.

### Índices mínimos esperados

Para entidades tenant-aware, avaliar índices compostos como:

```text
(tenant_id, id)
(tenant_id, status)
(tenant_id, created_at)
```

A composição final depende das queries reais e deve ser validada por repository/query service.
