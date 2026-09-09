# 00 — Inicialização obrigatória para IA

Este é o primeiro arquivo que uma IA deve ler antes de trabalhar no projeto.

## 1. Entenda o produto

Este não é um ERP genérico.

É um SaaS vertical para:

- Personal Trainers;
- consultorias online;
- assessorias esportivas;
- equipes de treinadores.

A relação principal é:

```text
Tenant
= Personal ou Assessoria

Tenant
   ↓
Personais
   ↓
Alunos
   ↓
Acompanhamento individual
```

## 2. Estrutura física obrigatória

```text
/
├── api/
├── app/
└── docs/
```

### Backend

Sempre trabalhar em:

```text
api/
```

### Frontend

Sempre trabalhar em:

```text
app/
```

### Documentação e planejamento

Sempre atualizar:

```text
docs/
```

quando uma decisão arquitetural ou funcional mudar.

## 3. Não começar editando

Antes de escrever código:

```text
inspecionar
↓
entender
↓
comparar com documentação
↓
identificar gaps
↓
planejar
↓
implementar
↓
testar
↓
documentar
```

## 4. Stack definida

A stack deste projeto já está definida. A IA deve validar a implementação existente, mas não substituir tecnologias por preferência.

```text
Frontend
- Vue 3
- TypeScript
- Pinia
- Tailwind CSS
- Flowbite

Backend
- Symfony 7
- PHP 8.4
- Doctrine ORM
- Symfony Messenger

Persistência
- MySQL

Infraestrutura MVP
- Docker
- Nginx
- filesystem local com volume persistente
- Messenger com Doctrine transport
```

Não utilizar S3, Cloudflare R2 ou Redis no MVP.

Se o código encontrado divergir desta decisão, a IA deve reportar o conflito antes de introduzir novas dependências.

Consultar obrigatoriamente `docs/20-STACK-TECNOLOGIA-INFRA.md`.

## 5. Regras obrigatórias

### Backend

- controllers finas;
- repositories para queries;
- services/application para regras;
- DTO/contracts na borda;
- migrations para banco;
- TenantContext no servidor;
- authorization no servidor;
- logs sem secrets;
- testes automatizados.

### Frontend

- organização por domínio;
- components reutilizáveis;
- stores/state isolados;
- não confiar no frontend para permissionamento;
- rotas e menus podem refletir permissionamento, mas API continua sendo autoridade;
- separar área do aluno e área administrativa quando necessário.

## 6. Regra de tenant

O frontend nunca deve decidir qual dado pertence ao tenant.

Exemplo proibido:

```text
POST /students
{
  tenantId: "outro-tenant"
}
```

Fonte correta:

```text
TenantContext.TenantId
```

## 7. Regra de aluno

Na área do aluno, preferir endpoints como:

```text
/api/me/workouts
/api/me/history
/api/me/progress
```

em vez de depender de um `StudentId` manipulável pelo cliente.

## 8. Regra de templates

```text
Template
   ↓
gera instância
   ↓
instância do aluno
```

Editar a instância não altera o template.

Editar o template não altera automaticamente alunos existentes.

## 9. Regra de histórico

Nunca sobrescrever planejamento publicado quando o histórico depende dele.

Versionar:

- fichas;
- templates;
- formulários de anamnese;
- planos de acompanhamento;
- exercícios quando necessário.

## 10. Definition of Done

Uma task só termina se:

- implementação concluída;
- migrations existentes;
- testes adicionados;
- testes executados;
- isolamento tenant validado;
- permissions validadas;
- documentação atualizada;
- critérios de aceite cumpridos.
