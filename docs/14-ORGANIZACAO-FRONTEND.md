# 14 — Organização do Frontend

Raiz obrigatória:

```text
app/
```

## Stack

```text
Vue 3
TypeScript
Pinia
Tailwind CSS
Flowbite
```

A aplicação deve usar Composition API e manter estado global apenas quando realmente compartilhado.

## Organização por domínio

```text
app/src/
├── modules/
│   ├── auth/
│   ├── tenants/
│   ├── students/
│   ├── trainers/
│   ├── exercises/
│   ├── workouts/
│   │   ├── templates/
│   │   ├── plans/
│   │   ├── execution/
│   │   └── history/
│   ├── assessments/
│   ├── anamnesis/
│   ├── goals/
│   ├── follow-up/
│   ├── analytics/
│   └── settings/
├── router/
├── stores/
└── shared/
```

Cada módulo pode conter:

```text
pages/
components/
services/
stores/
types/
validators/
```

## Regras

- componentes em Vue SFC;
- TypeScript obrigatório para código novo;
- Pinia para stores compartilhadas;
- chamadas HTTP isoladas em services/clients;
- não misturar DTO da API com estado de formulário quando houver diferenças relevantes;
- Tailwind CSS para composição visual;
- Flowbite como base de componentes, sem impedir componentes próprios quando necessário;
- evitar uma pasta global `components/` gigantesca;
- `shared/` somente para elementos verdadeiramente reutilizados;
- frontend nunca é fonte de verdade para autorização ou TenantContext.
