<script setup lang="ts">
import Badge from '@/shared/components/ui/Badge.vue';
import Button from '@/shared/components/ui/Button.vue';
import StudentLayout from '@/shared/layouts/StudentLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { workoutSessionService } from '@/modules/workouts/services/workoutSessionService';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  ArrowRight,
  Calendar,
  CheckCircle,
  Dumbbell,
  Flame,
  Target,
  Trophy,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const router = useRouter();
const startingWorkout = ref(false);

async function handleStartWorkout() {
  startingWorkout.value = true;
  try {
    const session = await workoutSessionService.start();
    router.push(`/portal/workout/live/${session.id}`);
  } catch (err: any) {
    alert(err.message || 'Erro ao iniciar sessão de treino.');
  } finally {
    startingWorkout.value = false;
  }
}
</script>

<template>
  <StudentLayout>
    <!-- Saudação Aluno -->
    <div class="mb-6">
      <span class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">
        Bem-vindo de volta
      </span>
      <h1 class="text-2xl font-bold font-display text-slate-900 dark:text-white mt-0.5">
        {{ authStore.user?.name.split(' ')[0] }} 💪
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
        Consistência é a chave para o seu objetivo!
      </p>
    </div>

    <!-- Card de Próximo Treino em Destaque -->
    <div class="rounded-3xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white p-6 shadow-glow mb-6 relative overflow-hidden">
      <div class="flex items-center justify-between mb-3">
        <Badge variant="neutral" size="sm" class="bg-white/20 text-white border-transparent">
          Hoje • Treino do Dia
        </Badge>
        <span class="text-xs font-medium text-white/80">45 a 60 min</span>
      </div>

      <h2 class="text-xl font-bold font-display tracking-tight text-white mb-1">
        Treino A - Peitoral & Tríceps
      </h2>
      <p class="text-xs text-white/80 mb-5">
        6 exercícios prescritos • Cargas personalizadas
      </p>

      <Button 
        @click="handleStartWorkout" 
        :disabled="startingWorkout"
        class="w-full bg-white text-brand-700 hover:bg-white/90 justify-center font-bold shadow-md" 
        size="lg"
      >
        <Dumbbell class="w-4 h-4 mr-1.5" />
        <span>{{ startingWorkout ? 'Iniciando Sessão...' : 'Iniciar Treino Agora' }}</span>
        <ArrowRight class="w-4 h-4 ml-1" />
      </Button>
    </div>

    <!-- Metas da Semana & Sequência (Streak) -->
    <div class="grid grid-cols-2 gap-3 mb-6">
      <!-- Card Frequência -->
      <div class="p-4 rounded-2xl glass-card flex flex-col justify-between">
        <div class="flex items-center justify-between text-slate-500 mb-2">
          <span class="text-[11px] font-semibold uppercase tracking-wider">Meta Semanal</span>
          <Target class="w-4 h-4 text-brand-500" />
        </div>
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-extrabold font-display text-slate-900 dark:text-white">3</span>
          <span class="text-xs text-slate-400">de 4 treinos</span>
        </div>
        <!-- Barra de Progresso -->
        <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
          <div class="bg-brand-500 h-full rounded-full" style="width: 75%"></div>
        </div>
      </div>

      <!-- Card Streak -->
      <div class="p-4 rounded-2xl glass-card flex flex-col justify-between">
        <div class="flex items-center justify-between text-slate-500 mb-2">
          <span class="text-[11px] font-semibold uppercase tracking-wider">Sequência</span>
          <Flame class="w-4 h-4 text-amber-500" />
        </div>
        <div class="flex items-baseline gap-1.5">
          <span class="text-2xl font-extrabold font-display text-slate-900 dark:text-white">5</span>
          <span class="text-xs text-slate-400">semanas</span>
        </div>
        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-2">
          Fogo total na rotina! 🔥
        </p>
      </div>
    </div>

    <!-- Atividades e Próximos Eventos -->
    <div class="rounded-3xl glass-card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <Trophy class="w-4 h-4 text-amber-500" />
          <span>Últimas Conquistas</span>
        </h3>
        <RouterLink to="/portal/history" class="text-xs text-brand-500 font-semibold hover:underline">
          Histórico
        </RouterLink>
      </div>

      <div class="space-y-3">
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-100/50 dark:bg-slate-800/40">
          <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <CheckCircle class="w-4 h-4" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
              Novo Recorde: Supino Reto (80 kg)
            </p>
            <p class="text-[10px] text-slate-400">
              Registrado na última terça-feira
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-100/50 dark:bg-slate-800/40">
          <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
            <Calendar class="w-4 h-4" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
              Próxima Reavaliação Física
            </p>
            <p class="text-[10px] text-slate-400">
              Em 14 dias com seu treinador
            </p>
          </div>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>
