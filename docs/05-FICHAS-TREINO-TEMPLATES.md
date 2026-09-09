# 05 — Fichas de Treino e Templates

## Conceito

```text
WorkoutTemplate
       ↓
versão
       ↓
aplicar ao aluno
       ↓
StudentWorkoutPlan
       ↓
versão individual
```

## Template

Campos:

- nome;
- objetivo;
- nível;
- divisão;
- dias;
- exercícios;
- grupos musculares;
- séries;
- repetições;
- carga inicial;
- descanso;
- RPE;
- tempo;
- técnica;
- observações.

## Aplicação

Ao aplicar template:

- criar instância independente;
- armazenar template origem;
- armazenar versão origem;
- copiar configuração;
- permitir personalização.

## Atualização de template

Nunca atualizar alunos automaticamente.

Oferecer:

```text
Nova versão disponível
Comparar
Selecionar alunos
Aplicar
Confirmar
```

## Versionamento

Versão publicada é imutável.

Alterar:

```text
v1
↓
duplicar como draft
↓
editar
↓
publicar
↓
v2
```
