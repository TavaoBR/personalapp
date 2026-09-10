# 💻 PersonalApp Frontend (App) — Vue 3 & TypeScript

> Interface moderna, responsiva e de alta performance desenvolvida em Vue 3, Vite, Tailwind CSS e Pinia para Personal Trainers e Alunos.

---

## 📋 Sumário
- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Padrões de Design e UI/UX](#padrões-de-design-e-uiux)
- [Estrutura Modular (Feature-Driven)](#estrutura-modular-feature-driven)
- [Instalação e Execução](#instalação-e-execução)
- [Gerenciamento de Estado & Autenticação](#gerenciamento-de-estado--autenticação)
- [Compilação para Produção & Tipagem](#compilação-para-produção--tipagem)

---

## ⚡ Tecnologias Utilizadas

- **Vue 3**: Composition API com a sintaxe `<script setup lang="ts">`.
- **TypeScript 5.x**: Tipagem estrita de payloads, respostas da API e componentes.
- **Vite 6**: Bundler com Hot Module Replacement (HMR) ultrarrápido.
- **Pinia 3**: Gerenciamento de estado reativo para autenticação, seleção de tenant e preferências.
- **Vue Router 4**: Roteamento SPA com proteção de rotas (Guards para `guestOnly`, `requiresAuth`, `roles` e `platformAdminOnly`).
- **Tailwind CSS 3.4**: Estilização baseada em tokens utilitários com suporte a Dark Mode.
- **Lucide Icons**: Ícones minimalistas e uniformes.

---

## 🎨 Padrões de Design e UI/UX

- **Dark Mode Primário**: Experiência visual imersiva em tons Slate-900 e Slate-950 com contrastes otimizados para redução de fadiga visual em academias.
- **Cores Semânticas**:
  - 🟢 **Esmeralda**: Sucesso, faturamento recebido, treinos concluídos e status ativos.
  - 🔵 **Índigo / Azul**: Planos, templates, navegação primária e dados informativos.
  - 🟡 **Âmbar**: Anamneses, status pendentes e alertas de atenção.
  - 🔴 **Rosa / Vermelho**: Inadimplência, faturas vencidas, cancelamentos e esforço máximo.
- **Feedback Imediato**: Modais interativos com estados de carregamento em botões (`submitting ? 'Salvando...' : 'Salvar'`) e tratamentos amigáveis de mensagens de erro.

---

## 📁 Estrutura Modular (Feature-Driven)

```text
app/src/
├── modules/
│   ├── auth/                # Login e autenticação
│   ├── dashboard/           # Visão geral do personal trainer
│   ├── students/            # Lista e prontuário completo do aluno (com 10 abas)
│   ├── trainers/            # Gestão da equipe de treinadores da assessoria
│   ├── workouts/            # Fichas, templates e módulo de Treino ao Vivo (Live Workout)
│   ├── exercises/           # Catálogo de exercícios e filtros
│   ├── assessments/         # Avaliações físicas, composição corporal e evolução
│   ├── analytics/           # Indicadores de retenção, volume de treino e PRs
│   ├── anamnesis/           # Construtor de questionários e respostas
│   ├── goals/               # Metas corporais e desafios quantitativos
│   ├── follow-up/           # Check-ins semanais, pareceres do treinador e cronogramas
│   ├── timeline/            # Linha do tempo auditável com feed cronológico
│   ├── financial/           # Faturamento, planos de consultoria e faturas
│   ├── saas-billing/        # Assinatura do espaço e cotas de uso
│   ├── admin/               # Console Platform Superadmin para governança
│   └── student-portal/      # Área exclusiva dos alunos (home, treinos, progresso)
├── shared/                  # Componentes reaproveitáveis (Button, Badge, Layouts, API Client)
├── stores/                  # Pinia stores (authStore)
├── router/                  # Configuração de rotas e navegação
└── main.ts                  # Ponto de entrada da aplicação Vue
```

---

## 🚀 Instalação e Execução

### 1. Pré-requisitos
- Node.js 18.0.0 ou superior
- npm 9.0.0 ou superior

### 2. Passos
```bash
# Instalar dependências
npm install

# Configurar variáveis de ambiente
cp .env.example .env

# Iniciar servidor de desenvolvimento com HMR
npm run dev
```
*Acesse a aplicação em `http://localhost:5173`.*

---

## 🔒 Gerenciamento de Estado & Autenticação

O `authStore` (`app/src/stores/auth.ts`) é responsável por:
1. Manter os tokens JWT e Refresh Token no `localStorage`.
2. Armazenar o usuário ativo e a lista de membresias/tenants às quais ele pertence.
3. Gerenciar a troca de contexto de tenant (`switchTenant(tenantId)`), atualizando automaticamente os cabeçalhos de todas as chamadas HTTP subsequentes via `apiClient`.
4. Expor getters reativos de papel: `isOwner`, `isAdmin`, `isTrainer`, `isStudent`, `isPlatformAdmin`.

---

## 📦 Compilação para Produção & Tipagem

Para validar os tipos TypeScript e compilar os bundles otimizados:
```bash
npm run build
```
O comando executa `vue-tsc -b` seguido por `vite build`, gerando os assets finais em `dist/`.
