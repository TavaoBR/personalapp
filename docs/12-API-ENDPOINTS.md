# 12 — Contratos e Endpoints da API

Todos implementados em `api/`.

## Students

```text
GET    /api/students
POST   /api/students
GET    /api/students/{id}
PATCH  /api/students/{id}

GET    /api/students/{id}/timeline
GET    /api/students/{id}/progress
GET    /api/students/{id}/metrics
```

## Workout Templates

```text
GET  /api/workout-templates
POST /api/workout-templates

GET  /api/workout-templates/{id}
GET  /api/workout-templates/{id}/versions
POST /api/workout-templates/{id}/versions
POST /api/workout-templates/{id}/assign
```

## Student Workout Plans

```text
GET  /api/students/{id}/workout-plans
POST /api/students/{id}/workout-plans

GET  /api/workout-plans/{id}
POST /api/workout-plans/{id}/versions
POST /api/workout-plans/{id}/activate
POST /api/workout-plans/{id}/archive
```

## Workout Sessions

```text
POST /api/workout-sessions
GET  /api/workout-sessions/{id}

POST /api/workout-sessions/{id}/exercises/{exerciseId}/sets
POST /api/workout-sessions/{id}/complete

GET  /api/students/{id}/workout-sessions
```

## Student self-service

Preferir:

```text
GET /api/me/workouts
GET /api/me/workout-history
GET /api/me/progress
GET /api/me/assessments
GET /api/me/goals
GET /api/me/anamneses
GET /api/me/timeline
```

## Anamnesis

```text
GET  /api/anamnesis-templates
POST /api/anamnesis-templates
POST /api/anamnesis-templates/{id}/versions

GET  /api/students/{id}/anamneses
POST /api/students/{id}/anamneses
```

## Goals

```text
GET   /api/students/{id}/goals
POST  /api/students/{id}/goals
PATCH /api/goals/{id}
POST  /api/goals/{id}/progress
```

## Follow-up

```text
GET  /api/follow-up-templates
POST /api/follow-up-templates
POST /api/follow-up-templates/{id}/versions

GET  /api/students/{id}/follow-up-plans
POST /api/students/{id}/follow-up-plans

GET  /api/follow-up/tasks
```
