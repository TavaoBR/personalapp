<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Back button -->
    <div class="flex items-center gap-3">
      <RouterLink 
        to="/students" 
        class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors flex items-center gap-1.5 text-xs font-medium"
      >
        <span>←</span>
        <span>Voltar para Alunos</span>
      </RouterLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando perfil do aluno...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-8 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <RouterLink to="/students" class="mt-4 inline-block px-4 py-2 text-xs bg-rose-500/20 text-rose-300 rounded-xl">
        Retornar à lista
      </RouterLink>
    </div>

    <!-- Main Content -->
    <div v-else-if="student" class="space-y-6">
      <!-- Top Profile Card -->
      <div class="p-6 rounded-3xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-md shadow-2xl relative overflow-hidden">
        <!-- Glow effect background -->
        <div class="absolute -right-10 -top-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div class="flex items-center gap-5">
            <!-- Big Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-500 text-white font-bold text-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 shrink-0">
              {{ getInitials(student.user.name) }}
            </div>

            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">
                  {{ student.user.name }}
                </h1>
                <span 
                  class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                  :class="student.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-500/10 text-slate-400 border-slate-500/30'"
                >
                  {{ student.status === 'ACTIVE' ? 'Ativo' : 'Inativo' }}
                </span>
              </div>
              
              <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-slate-400">
                <span class="flex items-center gap-1.5">
                  <span>✉️</span> {{ student.user.email }}
                </span>
                <span v-if="student.user.phone" class="flex items-center gap-1.5">
                  <span>📱</span> {{ student.user.phone }}
                </span>
                <span v-if="student.birth_date" class="flex items-center gap-1.5">
                  <span>🎂</span> {{ formatBirthDate(student.birth_date) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Trainer Box & Actions -->
          <div class="flex items-center gap-4">
            <div class="px-4 py-3 rounded-2xl bg-slate-950/60 border border-slate-800">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">
                Treinador Responsável
              </span>
              <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full" :class="student.trainer ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                <span class="text-sm font-semibold text-white">
                  {{ student.trainer?.name || 'Não Atribuído' }}
                </span>
                <button
                  v-if="canManage"
                  @click="isAssignModalOpen = true"
                  class="ml-2 text-xs text-emerald-400 hover:text-emerald-300 underline font-medium"
                >
                  Alterar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs (10 Domain Tabs) -->
      <div class="border-b border-slate-800 flex overflow-x-auto no-scrollbar gap-2 text-sm font-medium">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-3 whitespace-nowrap transition-all border-b-2 flex items-center gap-2"
          :class="activeTab === tab.id 
            ? 'border-emerald-400 text-emerald-400 font-semibold bg-emerald-500/5' 
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Tab Contents -->

      <!-- 1. Visão Geral -->
      <div v-if="activeTab === 'visao-geral'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Detalhes do Aluno -->
        <div class="lg:col-span-2 space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
              <span>📋</span> Informações Gerais
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div>
                <span class="text-slate-500 block mb-1">Gênero</span>
                <span class="text-slate-200 font-medium text-sm">{{ formatGender(student.gender) }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Data de Início</span>
                <span class="text-slate-200 font-medium text-sm">{{ formatDate(student.created_at) }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Contato de Emergência</span>
                <span class="text-slate-200 font-medium text-sm">{{ student.emergency_contact_name || 'Não informado' }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-1">Telefone de Emergência</span>
                <span class="text-slate-200 font-medium text-sm">{{ student.emergency_contact_phone || 'Não informado' }}</span>
              </div>
            </div>
          </div>

          <!-- Notas e Anotações -->
          <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📝</span> Anotações & Observações do Aluno
              </h3>
              <button
                v-if="!isEditingNotes"
                @click="startEditNotes"
                class="text-xs text-emerald-400 hover:text-emerald-300 font-medium"
              >
                Editar
              </button>
            </div>
            <div v-if="isEditingNotes" class="space-y-3">
              <textarea
                v-model="notesInput"
                rows="4"
                class="w-full px-3.5 py-2.5 bg-slate-950/70 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
              ></textarea>
              <div class="flex justify-end gap-2">
                <button
                  @click="isEditingNotes = false"
                  class="px-3 py-1.5 text-xs text-slate-400 hover:text-white rounded-lg"
                >
                  Cancelar
                </button>
                <button
                  @click="saveNotes"
                  :disabled="savingNotes"
                  class="px-4 py-1.5 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-lg"
                >
                  {{ savingNotes ? 'Salvando...' : 'Salvar' }}
                </button>
              </div>
            </div>
            <p v-else class="text-sm text-slate-300 whitespace-pre-wrap">
              {{ student.notes || 'Nenhuma anotação registrada ainda.' }}
            </p>
          </div>
        </div>

        <!-- Sidebar Rápida -->
        <div class="space-y-6">
          <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-900/80 to-slate-950/80 border border-slate-800">
            <h3 class="text-sm font-bold text-white mb-4">Ações Rápidas</h3>
            <div class="space-y-2.5">
              <button 
                @click="activeTab = 'treinos'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>🏋️ Montar / Ver Treino</span>
                <span>→</span>
              </button>
              <button 
                @click="activeTab = 'avaliacoes'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>📊 Nova Avaliação Física</span>
                <span>→</span>
              </button>
              <button 
                @click="activeTab = 'anamnese'" 
                class="w-full py-2.5 px-4 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center justify-between"
              >
                <span>📋 Visualizar Anamnese</span>
                <span>→</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Treinos -->
      <div v-else-if="activeTab === 'treinos'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🏋️</span> Fichas & Planos de Treino
            </h3>
            <p class="text-xs text-slate-400">Fichas ativas e histórico de periodização prescrito para este aluno.</p>
          </div>

          <RouterLink
            to="/workouts/builder"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all"
          >
            + Montar Novo Treino
          </RouterLink>
        </div>

        <div v-if="loadingPlans" class="py-12 text-center text-xs text-slate-400">
          Carregando fichas de treino...
        </div>

        <div v-else-if="studentPlans.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-3">
            🏋️
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma ficha prescrita</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Este aluno ainda não possui uma ficha de treino ativa.
          </p>
          <RouterLink 
            to="/workouts" 
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl inline-block"
          >
            Aplicar um Template Existente
          </RouterLink>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div 
            v-for="p in studentPlans" 
            :key="p.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between"
          >
            <div>
              <div class="flex items-center justify-between mb-2">
                <span 
                  class="px-2 py-0.5 text-[10px] font-bold rounded-md border"
                  :class="p.status === 'ACTIVE' 
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' 
                    : 'bg-slate-500/10 text-slate-400 border-slate-500/30'"
                >
                  {{ p.status === 'ACTIVE' ? 'Ficha Ativa' : p.status }}
                </span>
                <span class="text-[10px] text-slate-500">{{ formatDate(p.created_at) }}</span>
              </div>

              <h4 class="font-bold text-white text-base leading-snug">{{ p.name }}</h4>
              <p v-if="p.template_name" class="text-xs text-slate-400 mt-1">
                Origem: <span class="text-slate-300">{{ p.template_name }}</span>
              </p>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-800 flex items-center justify-between">
              <span class="text-xs text-slate-400">
                {{ p.current_version?.days?.length || 0 }} dias de treino
              </span>
              <RouterLink 
                :to="`/workouts/plans/${p.id}`"
                class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
              >
                Abrir Ficha →
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Evolução -->
      <div v-else-if="activeTab === 'evolucao'" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📈</span> Evolução de Métricas
            </h3>
            <p class="text-xs text-slate-400">Acompanhamento temporal e comparativos de composição corporal.</p>
          </div>
          <button
            @click="isRecordMetricModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl shadow-lg shadow-purple-600/20 transition-all flex items-center gap-1.5"
          >
            <span>⚖️</span>
            <span>Registrar Medida Rápida</span>
          </button>
        </div>

        <div v-if="loadingProgress" class="py-12 text-center text-xs text-slate-400">
          Carregando dados de evolução...
        </div>

        <div v-if="progressData" class="space-y-6">
          <div class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400 font-medium">Peso Corporal Mais Recente</span>
              <span 
                v-if="progressData.delta !== null"
                class="px-2.5 py-0.5 text-xs font-bold rounded-full border font-mono"
                :class="(progressData.delta || 0) <= 0 
                  ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                  : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
              >
                {{ (progressData.delta || 0) > 0 ? '+' : '' }}{{ progressData.delta }} {{ progressData.unit }} ({{ progressData.delta_percentage }}%)
              </span>
            </div>

            <div class="flex items-baseline gap-2">
              <span class="text-4xl font-black text-white font-mono tracking-tight">
                {{ progressData.latest_value ?? '-' }}
              </span>
              <span class="text-base font-semibold text-purple-400">{{ progressData.unit }}</span>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-800/80 text-xs">
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Inicial</span>
                <span class="font-bold text-slate-200 font-mono">{{ progressData.initial_value ?? '-' }} {{ progressData.unit }}</span>
              </div>
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Mínimo</span>
                <span class="font-bold text-slate-200 font-mono">{{ progressData.min_value ?? '-' }} {{ progressData.unit }}</span>
              </div>
              <div>
                <span class="text-[10px] text-slate-500 block uppercase">Máximo</span>
                <span class="font-bold text-slate-200 font-mono">{{ progressData.max_value ?? '-' }} {{ progressData.unit }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3.1 Comparativo de Treinos & Aderência -->
        <PeriodComparisonCard
          :analytics="studentAnalytics"
          :loading="loadingAnalytics"
          :selected-days="analyticsDays"
          @select-days="loadStudentAnalytics"
        />

        <!-- 3.2 Recordes Pessoais (PRs) de Carga -->
        <PRsListCard
          :prs="studentPRs"
          :loading="loadingPRs"
        />
      </div>

      <!-- 4. Avaliações -->
      <div v-else-if="activeTab === 'avaliacoes'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📊</span> Avaliações Físicas
            </h3>
            <p class="text-xs text-slate-400">Histórico de laudos antropométricos e bioimpedância.</p>
          </div>

          <button
            @click="isCreateAssessmentModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl transition-all"
          >
            + Iniciar Avaliação
          </button>
        </div>

        <div v-if="loadingAssessments" class="py-12 text-center text-xs text-slate-400">
          Carregando avaliações...
        </div>

        <div v-else-if="assessments.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl mb-3">
            📊
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma avaliação realizada</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Registre a primeira avaliação física com peso, gordura e circunferências.
          </p>
          <button
            @click="isCreateAssessmentModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-500 rounded-xl"
          >
            Realizar Avaliação Agora
          </button>
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="a in assessments" 
            :key="a.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="font-bold text-white text-sm">📅 {{ formatDate(a.date) }}</span>
                <span class="px-2 py-0.5 text-[10px] rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">
                  {{ a.metrics_count }} métricas coletadas
                </span>
              </div>
              <p v-if="a.notes" class="text-xs text-slate-400 italic">"{{ a.notes }}"</p>
              <span v-if="a.trainer_name" class="text-[11px] text-slate-500 block">
                Avaliador: {{ a.trainer_name }}
              </span>
            </div>

            <button
              @click="openAssessmentDetail(a.id)"
              class="px-3.5 py-1.5 text-xs font-semibold text-purple-400 hover:text-purple-300 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 rounded-xl"
            >
              Ver Detalhes →
            </button>
          </div>
        </div>
      </div>

      <!-- 5. Anamnese -->
      <div v-else-if="activeTab === 'anamnese'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📋</span> Anamneses & Histórico Clínico
            </h3>
            <p class="text-xs text-slate-400">Questionários de saúde, patologias, cirurgias e restrições articulares.</p>
          </div>

          <button
            @click="isFillAnamnesisModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 rounded-xl transition-all font-display"
          >
            + Preencher Anamnese
          </button>
        </div>

        <div v-if="loadingAnamneses" class="py-12 text-center text-xs text-slate-400">
          Carregando anamneses do aluno...
        </div>

        <div v-else-if="anamneses.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl mb-3">
            📋
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma anamnese registrada</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Aplique um questionário de anamnese para registrar o histórico de saúde do aluno.
          </p>
          <button
            @click="isFillAnamnesisModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 rounded-xl font-display"
          >
            Preencher Agora
          </button>
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="a in anamneses" 
            :key="a.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex items-center justify-between"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="font-bold text-white text-sm">📋 {{ a.template_title }}</span>
                <span class="px-2 py-0.5 text-[10px] rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-mono">
                  v{{ a.version_number }}
                </span>
                <span class="text-xs text-slate-400">
                  • {{ formatDate(a.answered_at) }}
                </span>
              </div>
              <span class="text-xs text-slate-400 block">
                {{ a.answers_count }} perguntas respondidas
              </span>
            </div>

            <button
              @click="openAnamnesisDetail(a.id)"
              class="px-3.5 py-1.5 text-xs font-semibold text-amber-400 hover:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 rounded-xl"
            >
              Visualizar Prontuário →
            </button>
          </div>
        </div>
      </div>

      <!-- 6. Metas -->
      <div v-else-if="activeTab === 'metas'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🎯</span> Metas & Desafios Corporais
            </h3>
            <p class="text-xs text-slate-400">Objetivos de peso, percentual de gordura e cargas máximas.</p>
          </div>

          <button
            @click="isCreateGoalModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-lg shadow-rose-600/20 transition-all font-display"
          >
            + Nova Meta
          </button>
        </div>

        <div v-if="loadingGoals" class="py-12 text-center text-xs text-slate-400">
          Carregando metas do aluno...
        </div>

        <div v-else-if="goals.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
          <div class="w-12 h-12 mx-auto rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-xl mb-3">
            🎯
          </div>
          <h4 class="text-base font-semibold text-white">Nenhuma meta estabelecida</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Defina metas quantitativas para manter a motivação e medir resultados ao longo do tempo.
          </p>
          <button
            @click="isCreateGoalModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl font-display shadow-lg shadow-rose-600/20"
          >
            Definir Primeira Meta
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="g in goals" 
            :key="g.id"
            class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4 group"
          >
            <div class="space-y-2.5">
              <div class="flex items-start justify-between gap-2">
                <h4 class="text-sm font-bold text-white group-hover:text-rose-300 transition-colors">
                  {{ g.title }}
                </h4>
                <span 
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full border shrink-0"
                  :class="g.status === 'COMPLETED' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : g.status === 'ABANDONED' ? 'bg-slate-500/10 text-slate-400 border-slate-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
                >
                  {{ g.status === 'COMPLETED' ? 'Concluída' : g.status === 'ABANDONED' ? 'Cancelada' : 'Em Andamento' }}
                </span>
              </div>

              <!-- Progress Bar -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-400">Progresso</span>
                  <span class="font-bold text-rose-400 font-mono">{{ g.progress_percentage }}%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                  <div 
                    class="h-full rounded-full transition-all duration-500 bg-gradient-to-r from-rose-500 to-amber-400"
                    :style="{ width: `${Math.min(100, Math.max(0, g.progress_percentage))}%` }"
                  ></div>
                </div>
              </div>

              <!-- 3 Values -->
              <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-800/80 text-center text-xs">
                <div>
                  <span class="text-[10px] text-slate-500 block uppercase">Inicial</span>
                  <span class="font-bold text-slate-300 font-mono">{{ g.initial_value ?? '-' }} {{ g.unit }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-500 block uppercase">Atual</span>
                  <span class="font-bold text-rose-400 font-mono">{{ g.current_value ?? g.initial_value ?? '-' }} {{ g.unit }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-slate-500 block uppercase">Alvo</span>
                  <span class="font-bold text-emerald-400 font-mono">{{ g.target_value ?? '-' }} {{ g.unit }}</span>
                </div>
              </div>
            </div>

            <!-- Actions Footer -->
            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span v-if="g.target_date" class="text-slate-500 text-[11px]">
                🎯 Prazo: {{ formatDate(g.target_date) }}
              </span>
              <span v-else class="text-slate-500 text-[11px]">Sem prazo limite</span>

              <div class="flex items-center gap-2">
                <button
                  v-if="g.status === 'IN_PROGRESS'"
                  @click="openRecordProgress(g)"
                  class="px-2.5 py-1 text-xs font-semibold text-rose-300 hover:text-white bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-lg transition-all"
                >
                  + Medição
                </button>
                <button
                  @click="openGoalDetail(g.id)"
                  class="px-2.5 py-1 text-xs font-semibold text-slate-400 hover:text-white rounded-lg transition-all"
                >
                  Ver →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 7. Acompanhamento -->
      <div v-else-if="activeTab === 'acompanhamento'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📅</span> Acompanhamento & Rotina Semanal
            </h3>
            <p class="text-xs text-slate-400">Feedback de esforço, sono, dores musculares e planos de tarefas.</p>
          </div>

          <div class="flex items-center gap-2 self-start">
            <button
              @click="isNewCheckInModalOpen = true"
              class="px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg shadow-blue-600/20 transition-all font-display flex items-center gap-1.5"
            >
              <span>➕</span>
              <span>Novo Check-in</span>
            </button>
            <button
              @click="isPrescribePlanModalOpen = true"
              class="px-3.5 py-2 text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1.5"
            >
              <span>📋</span>
              <span>Prescrever Plano</span>
            </button>
          </div>
        </div>

        <div v-if="loadingFollowUp" class="py-12 text-center text-xs text-slate-400">
          Carregando acompanhamento e check-ins...
        </div>

        <div v-else class="space-y-6">
          <!-- 7.1 Planos de Acompanhamento (Cronogramas) -->
          <div v-if="followUpPlans.length > 0" class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
              Planos de Tarefas & Checkpoints Ativos ({{ followUpPlans.length }})
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div 
                v-for="p in followUpPlans" 
                :key="p.id"
                class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-2.5"
              >
                <div class="flex items-center justify-between">
                  <h5 class="text-xs font-bold text-white">{{ p.title }}</h5>
                  <span class="text-[10px] font-mono text-blue-400 font-bold">
                    {{ p.completed_tasks_count }}/{{ p.tasks_count }} tarefas
                  </span>
                </div>

                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                  <div 
                    class="bg-blue-500 h-full rounded-full transition-all duration-500"
                    :style="{ width: `${p.progress_percentage}%` }"
                  ></div>
                </div>

                <div class="pt-2 flex items-center justify-between text-xs">
                  <span class="text-[11px] text-slate-500">Início: {{ formatDate(p.start_date) }}</span>
                  <button
                    @click="openPlanDetail(p.id)"
                    class="text-xs font-semibold text-blue-400 hover:text-blue-300"
                  >
                    Ver Tarefas →
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 7.2 Check-ins Periódicos -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
              Histórico de Check-ins ({{ checkIns.length }})
            </h4>

            <div v-if="checkIns.length === 0" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
              <div class="w-12 h-12 mx-auto rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl mb-3">
                📅
              </div>
              <h4 class="text-base font-semibold text-white">Nenhum check-in registrado</h4>
              <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                Registre os relatos de sono, energia e dores musculares para acompanhamento próximo do aluno.
              </p>
              <button
                @click="isNewCheckInModalOpen = true"
                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl font-display shadow-lg shadow-blue-600/20"
              >
                Registrar Primeiro Check-in
              </button>
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="c in checkIns" 
                :key="c.id"
                class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-white">📅 {{ formatDate(c.check_in_date) }}</span>
                    <span 
                      class="px-2 py-0.5 text-[10px] rounded-full border font-semibold"
                      :class="c.trainer_feedback 
                        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                        : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
                    >
                      {{ c.trainer_feedback ? 'Respondido pelo Treinador' : 'Aguardando Feedback' }}
                    </span>
                  </div>

                  <button
                    @click="openFeedback(c)"
                    class="px-3 py-1 text-xs font-semibold text-blue-400 hover:text-blue-300 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-xl transition-all"
                  >
                    {{ c.trainer_feedback ? 'Editar Parecer' : '💬 Dar Parecer' }}
                  </button>
                </div>

                <!-- 4 Metrics Chips -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                  <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">⚡ Energia</span>
                    <span class="font-bold text-amber-400 font-mono">{{ c.energy_level ?? '-' }}/5</span>
                  </div>
                  <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">😴 Sono</span>
                    <span class="font-bold text-indigo-400 font-mono">{{ c.sleep_quality ?? '-' }}/5</span>
                  </div>
                  <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">🔥 Dor Muscular</span>
                    <span class="font-bold text-rose-400 font-mono">{{ c.soreness_level ?? '-' }}/5</span>
                  </div>
                  <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">🥗 Dieta</span>
                    <span class="font-bold text-emerald-400 font-mono">{{ c.diet_adherence ?? '-' }}/5</span>
                  </div>
                </div>

                <!-- Aluno Notes -->
                <p v-if="c.notes" class="text-xs text-slate-300 italic p-3 rounded-xl bg-slate-950/50 border border-slate-800/60">
                  "{{ c.notes }}"
                </p>

                <!-- Feedback Treinador -->
                <div v-if="c.trainer_feedback" class="p-3 rounded-xl bg-blue-500/5 border border-blue-500/20 text-xs space-y-1">
                  <span class="text-[10px] uppercase font-bold text-blue-400 block">Orientação do Treinador:</span>
                  <p class="text-slate-200">{{ c.trainer_feedback }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 8. Financeiro -->
      <div v-else-if="activeTab === 'financeiro'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-3">
          💰
        </div>
        <h3 class="text-lg font-semibold text-white">Financeiro & Assinatura do Aluno</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Planos contratados, faturas emitidas e controle de mensalidades da consultoria.
        </p>
      </div>

      <!-- 9. Timeline -->
      <div v-else-if="activeTab === 'timeline'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>⏳</span> Linha do Tempo Auditável
            </h3>
            <p class="text-xs text-slate-400">Histórico de prescrições, execuções, avaliações e anotações clínicas.</p>
          </div>

          <button
            @click="isCreateTimelineModalOpen = true"
            class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/20 transition-all font-display self-start flex items-center gap-1.5"
          >
            <span>➕</span>
            <span>Registrar Evento Manual</span>
          </button>
        </div>

        <!-- Filtros Rápidos -->
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 text-xs">
          <button
            v-for="f in timelineFilters"
            :key="f.type"
            @click="changeTimelineFilter(f.type)"
            class="px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap border"
            :class="selectedTimelineFilter === f.type 
              ? 'bg-indigo-600 text-white border-indigo-600 font-semibold shadow-md shadow-indigo-600/20' 
              : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'"
          >
            {{ f.label }}
          </button>
        </div>

        <!-- Feed de Eventos -->
        <TimelineFeed
          :events="timelineEvents"
          :loading="loadingTimeline"
        />
      </div>

      <!-- 10. Arquivos -->
      <div v-else-if="activeTab === 'arquivos'" class="p-8 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
        <div class="w-12 h-12 mx-auto rounded-xl bg-slate-500/10 text-slate-400 flex items-center justify-center text-xl mb-3">
          📁
        </div>
        <h3 class="text-lg font-semibold text-white">Arquivos & Fotos</h3>
        <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
          Armazenamento seguro de exames médicos, fotos posturais e documentos do aluno.
        </p>
      </div>

      <!-- Modal de Atribuição de Treinador -->
      <AssignTrainerModal
        :is-open="isAssignModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        :current-trainer-id="student.trainer?.id"
        @close="isAssignModalOpen = false"
        @assigned="loadStudent"
      />

      <!-- Modais de Avaliação e Métricas -->
      <CreateAssessmentModal
        :is-open="isCreateAssessmentModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isCreateAssessmentModalOpen = false"
        @created="loadAssessments"
      />

      <RecordMetricModal
        :is-open="isRecordMetricModalOpen"
        :student-id="student.id"
        @close="isRecordMetricModalOpen = false"
        @recorded="loadProgressData"
      />

      <AssessmentDetailModal
        :is-open="isAssessmentDetailModalOpen"
        :assessment="selectedAssessmentDetail"
        @close="isAssessmentDetailModalOpen = false"
      />

      <!-- Modais de Anamnese -->
      <FillAnamnesisModal
        :is-open="isFillAnamnesisModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isFillAnamnesisModalOpen = false"
        @submitted="loadAnamneses"
      />

      <AnamnesisDetailModal
        :is-open="isAnamnesisDetailModalOpen"
        :anamnesis="selectedAnamnesisDetail"
        @close="isAnamnesisDetailModalOpen = false"
      />

      <!-- Modais de Metas -->
      <CreateGoalModal
        :is-open="isCreateGoalModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isCreateGoalModalOpen = false"
        @created="loadGoals"
      />

      <RecordGoalProgressModal
        :is-open="isRecordGoalProgressModalOpen"
        :goal="selectedGoalForProgress"
        @close="isRecordGoalProgressModalOpen = false"
        @recorded="loadGoals"
      />

      <GoalDetailModal
        :is-open="isGoalDetailModalOpen"
        :goal="selectedGoalDetail"
        @close="isGoalDetailModalOpen = false"
        @updated="loadGoals"
      />

      <!-- Modais de Acompanhamento & Check-in -->
      <NewCheckInModal
        :is-open="isNewCheckInModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isNewCheckInModalOpen = false"
        @created="loadFollowUpData"
      />

      <CheckInFeedbackModal
        :is-open="isFeedbackModalOpen"
        :check-in="selectedCheckInForFeedback"
        @close="isFeedbackModalOpen = false"
        @saved="loadFollowUpData"
      />

      <PrescribePlanModal
        :is-open="isPrescribePlanModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isPrescribePlanModalOpen = false"
        @prescribed="loadFollowUpData"
      />

      <FollowUpPlanDetailModal
        :is-open="isPlanDetailModalOpen"
        :plan="selectedPlanDetail"
        @close="isPlanDetailModalOpen = false"
        @updated="loadFollowUpData"
      />

      <!-- Modal de Registro de Evento na Timeline -->
      <CreateTimelineEventModal
        :is-open="isCreateTimelineModalOpen"
        :student-id="student.id"
        :student-name="student.user.name"
        @close="isCreateTimelineModalOpen = false"
        @created="loadTimeline(selectedTimelineFilter)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { studentService } from '../services/studentService'
import type { StudentDetail } from '../types'
import { workoutPlanService } from '@/modules/workouts/services/workoutPlanService'
import type { StudentWorkoutPlanSummary } from '@/modules/workouts/types'
import { assessmentService } from '@/modules/assessments/services/assessmentService'
import type { Assessment, AssessmentDetail, StudentProgressData } from '@/modules/assessments/types'
import { useAuthStore } from '@/stores/auth'
import AssignTrainerModal from '../components/AssignTrainerModal.vue'
import CreateAssessmentModal from '@/modules/assessments/components/CreateAssessmentModal.vue'
import RecordMetricModal from '@/modules/assessments/components/RecordMetricModal.vue'
import AssessmentDetailModal from '@/modules/assessments/components/AssessmentDetailModal.vue'
import { analyticsService } from '@/modules/analytics/services/analyticsService'
import type { StudentAnalyticsResponse, PersonalRecord } from '@/modules/analytics/types'
import PeriodComparisonCard from '@/modules/analytics/components/PeriodComparisonCard.vue'
import PRsListCard from '@/modules/analytics/components/PRsListCard.vue'
import { anamnesisService } from '@/modules/anamnesis/services/anamnesisService'
import type { StudentAnamnesisSummary, StudentAnamnesisDetail } from '@/modules/anamnesis/types'
import FillAnamnesisModal from '@/modules/anamnesis/components/FillAnamnesisModal.vue'
import AnamnesisDetailModal from '@/modules/anamnesis/components/AnamnesisDetailModal.vue'
import { goalService } from '@/modules/goals/services/goalService'
import type { StudentGoalSummary, StudentGoalDetail } from '@/modules/goals/types'
import CreateGoalModal from '@/modules/goals/components/CreateGoalModal.vue'
import RecordGoalProgressModal from '@/modules/goals/components/RecordGoalProgressModal.vue'
import GoalDetailModal from '@/modules/goals/components/GoalDetailModal.vue'
import { followUpService } from '@/modules/follow-up/services/followUpService'
import type { StudentCheckIn, FollowUpPlanSummary, FollowUpPlanDetail } from '@/modules/follow-up/types'
import NewCheckInModal from '@/modules/follow-up/components/NewCheckInModal.vue'
import CheckInFeedbackModal from '@/modules/follow-up/components/CheckInFeedbackModal.vue'
import PrescribePlanModal from '@/modules/follow-up/components/PrescribePlanModal.vue'
import FollowUpPlanDetailModal from '@/modules/follow-up/components/FollowUpPlanDetailModal.vue'
import { timelineService } from '@/modules/timeline/services/timelineService'
import type { TimelineEvent } from '@/modules/timeline/types'
import TimelineFeed from '@/modules/timeline/components/TimelineFeed.vue'
import CreateTimelineEventModal from '@/modules/timeline/components/CreateTimelineEventModal.vue'

const route = useRoute()
const authStore = useAuthStore()

const studentId = computed(() => route.params.id as string)
const student = ref<StudentDetail | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const activeTab = ref('visao-geral')

const studentPlans = ref<StudentWorkoutPlanSummary[]>([])
const loadingPlans = ref(false)

const assessments = ref<Assessment[]>([])
const loadingAssessments = ref(false)
const isCreateAssessmentModalOpen = ref(false)
const isAssessmentDetailModalOpen = ref(false)
const selectedAssessmentDetail = ref<AssessmentDetail | null>(null)

const progressData = ref<StudentProgressData | null>(null)
const loadingProgress = ref(false)
const isRecordMetricModalOpen = ref(false)

const studentAnalytics = ref<StudentAnalyticsResponse | null>(null)
const loadingAnalytics = ref(false)
const analyticsDays = ref(30)
const studentPRs = ref<PersonalRecord[]>([])
const loadingPRs = ref(false)

const anamneses = ref<StudentAnamnesisSummary[]>([])
const loadingAnamneses = ref(false)
const isFillAnamnesisModalOpen = ref(false)
const isAnamnesisDetailModalOpen = ref(false)
const selectedAnamnesisDetail = ref<StudentAnamnesisDetail | null>(null)

const goals = ref<StudentGoalSummary[]>([])
const loadingGoals = ref(false)
const isCreateGoalModalOpen = ref(false)
const isRecordGoalProgressModalOpen = ref(false)
const isGoalDetailModalOpen = ref(false)
const selectedGoalForProgress = ref<StudentGoalSummary | null>(null)
const selectedGoalDetail = ref<StudentGoalDetail | null>(null)

const checkIns = ref<StudentCheckIn[]>([])
const followUpPlans = ref<FollowUpPlanSummary[]>([])
const loadingFollowUp = ref(false)
const isNewCheckInModalOpen = ref(false)
const isFeedbackModalOpen = ref(false)
const selectedCheckInForFeedback = ref<StudentCheckIn | null>(null)
const isPrescribePlanModalOpen = ref(false)
const isPlanDetailModalOpen = ref(false)
const selectedPlanDetail = ref<FollowUpPlanDetail | null>(null)

const timelineEvents = ref<TimelineEvent[]>([])
const loadingTimeline = ref(false)
const selectedTimelineFilter = ref('')
const isCreateTimelineModalOpen = ref(false)

const timelineFilters = [
  { type: '', label: 'Todos os Eventos' },
  { type: 'WORKOUT_COMPLETED', label: '🏋️ Treinos' },
  { type: 'ASSESSMENT_RECORDED', label: '📊 Avaliações' },
  { type: 'GOAL_ACHIEVED', label: '🎯 Metas' },
  { type: 'CHECKIN_SUBMITTED', label: '💬 Check-ins' },
  { type: 'NOTE_ADDED', label: '📝 Anotações' },
]

const isAssignModalOpen = ref(false)
const isEditingNotes = ref(false)
const notesInput = ref('')
const savingNotes = ref(false)

const canManage = computed(() => {
  const role = authStore.activeRole
  return role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'
})

const tabs = [
  { id: 'visao-geral', label: 'Visão Geral', icon: '👤' },
  { id: 'treinos', label: 'Treinos', icon: '🏋️' },
  { id: 'evolucao', label: 'Evolução', icon: '📈' },
  { id: 'avaliacoes', label: 'Avaliações', icon: '📊' },
  { id: 'anamnese', label: 'Anamnese', icon: '📋' },
  { id: 'metas', label: 'Metas', icon: '🎯' },
  { id: 'acompanhamento', label: 'Acompanhamento', icon: '📅' },
  { id: 'financeiro', label: 'Financeiro', icon: '💰' },
  { id: 'timeline', label: 'Timeline', icon: '⏳' },
  { id: 'arquivos', label: 'Arquivos', icon: '📁' },
]

async function loadStudent() {
  loading.value = true
  errorMessage.value = ''
  try {
    student.value = await studentService.getById(studentId.value)
    notesInput.value = student.value.notes || ''
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar detalhes do aluno.'
  } finally {
    loading.value = false
  }
}

async function loadPlans() {
  loadingPlans.value = true
  try {
    studentPlans.value = await workoutPlanService.listByStudent(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar planos de treino:', err)
  } finally {
    loadingPlans.value = false
  }
}

async function loadAssessments() {
  loadingAssessments.value = true
  try {
    assessments.value = await assessmentService.listByStudent(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar avaliações:', err)
  } finally {
    loadingAssessments.value = false
  }
}

async function loadProgressData() {
  loadingProgress.value = true
  try {
    progressData.value = await assessmentService.getProgress(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar métricas de evolução:', err)
  } finally {
    loadingProgress.value = false
  }
}

async function loadStudentAnalytics(days: number = 30) {
  analyticsDays.value = days
  loadingAnalytics.value = true
  try {
    const now = new Date()
    const startA = new Date(now.getTime() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    const endA = now.toISOString().split('T')[0]
    const startB = new Date(now.getTime() - days * 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    const endB = startA

    studentAnalytics.value = await analyticsService.getStudentAnalytics(studentId.value, {
      start_a: startA,
      end_a: endA,
      start_b: startB,
      end_b: endB
    })
  } catch (err) {
    console.error('Erro ao carregar analytics do aluno:', err)
  } finally {
    loadingAnalytics.value = false
  }
}

async function loadStudentPRs() {
  loadingPRs.value = true
  try {
    studentPRs.value = await analyticsService.getStudentPRs(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar PRs do aluno:', err)
  } finally {
    loadingPRs.value = false
  }
}

async function openAssessmentDetail(id: string) {
  try {
    selectedAssessmentDetail.value = await assessmentService.getById(id)
    isAssessmentDetailModalOpen.value = true
  } catch (err) {
    console.error('Erro ao abrir detalhes da avaliação:', err)
  }
}

async function loadAnamneses() {
  loadingAnamneses.value = true
  try {
    anamneses.value = await anamnesisService.listStudentAnamneses(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar anamneses do aluno:', err)
  } finally {
    loadingAnamneses.value = false
  }
}

async function openAnamnesisDetail(id: string) {
  try {
    selectedAnamnesisDetail.value = await anamnesisService.getAnamnesisDetail(id)
    isAnamnesisDetailModalOpen.value = true
  } catch (err) {
    console.error('Erro ao abrir detalhes da anamnese:', err)
  }
}

async function loadGoals() {
  loadingGoals.value = true
  try {
    goals.value = await goalService.listByStudent(studentId.value)
  } catch (err) {
    console.error('Erro ao carregar metas do aluno:', err)
  } finally {
    loadingGoals.value = false
  }
}

function openRecordProgress(goal: StudentGoalSummary) {
  selectedGoalForProgress.value = goal
  isRecordGoalProgressModalOpen.value = true
}

async function openGoalDetail(id: string) {
  try {
    selectedGoalDetail.value = await goalService.getById(id)
    isGoalDetailModalOpen.value = true
  } catch (err) {
    console.error('Erro ao abrir detalhes da meta:', err)
  }
}

async function loadFollowUpData() {
  loadingFollowUp.value = true
  try {
    const [fetchedCheckIns, fetchedPlans] = await Promise.all([
      followUpService.listStudentCheckIns(studentId.value),
      followUpService.listStudentPlans(studentId.value)
    ])
    checkIns.value = fetchedCheckIns
    followUpPlans.value = fetchedPlans
  } catch (err) {
    console.error('Erro ao carregar follow-up do aluno:', err)
  } finally {
    loadingFollowUp.value = false
  }
}

function openFeedback(c: StudentCheckIn) {
  selectedCheckInForFeedback.value = c
  isFeedbackModalOpen.value = true
}

async function openPlanDetail(id: string) {
  try {
    selectedPlanDetail.value = await followUpService.getPlanById(id)
    isPlanDetailModalOpen.value = true
  } catch (err) {
    console.error('Erro ao carregar detalhes do plano:', err)
  }
}

async function loadTimeline(eventType?: string) {
  loadingTimeline.value = true
  try {
    const res = await timelineService.listByStudent(studentId.value, {
      event_type: eventType || undefined
    })
    timelineEvents.value = res.events
  } catch (err) {
    console.error('Erro ao carregar timeline:', err)
  } finally {
    loadingTimeline.value = false
  }
}

function changeTimelineFilter(type: string) {
  selectedTimelineFilter.value = type
  loadTimeline(type)
}

watch(activeTab, (newTab) => {
  if (newTab === 'treinos') {
    loadPlans()
  } else if (newTab === 'avaliacoes') {
    loadAssessments()
  } else if (newTab === 'evolucao') {
    loadProgressData()
    loadStudentAnalytics(analyticsDays.value)
    loadStudentPRs()
  } else if (newTab === 'anamnese') {
    loadAnamneses()
  } else if (newTab === 'metas') {
    loadGoals()
  } else if (newTab === 'acompanhamento') {
    loadFollowUpData()
  } else if (newTab === 'timeline') {
    loadTimeline(selectedTimelineFilter.value)
  }
})

function startEditNotes() {
  notesInput.value = student.value?.notes || ''
  isEditingNotes.value = true
}

async function saveNotes() {
  savingNotes.value = true
  try {
    await studentService.update(studentId.value, { notes: notesInput.value })
    if (student.value) {
      student.value.notes = notesInput.value
    }
    isEditingNotes.value = false
  } catch (err: any) {
    alert(err.message || 'Erro ao salvar notas.')
  } finally {
    savingNotes.value = false
  }
}

function getInitials(name: string): string {
  if (!name) return 'A'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function formatGender(gender?: string | null): string {
  if (gender === 'M') return 'Masculino'
  if (gender === 'F') return 'Feminino'
  return gender || 'Não informado'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

function formatBirthDate(dateStr: string): string {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  const age = Math.floor((Date.now() - date.getTime()) / (365.25 * 24 * 60 * 60 * 1000))
  return `${date.toLocaleDateString('pt-BR')} (${age} anos)`
}

onMounted(() => {
  loadStudent()
})
</script>
