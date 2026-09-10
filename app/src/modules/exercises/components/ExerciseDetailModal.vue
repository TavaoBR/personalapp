<template>
  <div v-if="isOpen && exercise" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-start justify-between pb-4 border-b border-slate-800">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                {{ formatMuscleGroup(exercise.muscle_group) }}
              </span>
              <span 
                class="px-2 py-0.5 text-[10px] font-bold rounded-md border"
                :class="exercise.is_custom 
                  ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' 
                  : 'bg-blue-500/10 text-blue-400 border-blue-500/20'"
              >
                {{ exercise.is_custom ? 'Personalizado' : 'Global' }}
              </span>
              <span v-if="exercise.version" class="text-[10px] text-slate-500 font-mono">
                v{{ exercise.version }}
              </span>
            </div>
            <h2 class="text-xl font-bold text-white tracking-tight">
              {{ exercise.name }}
            </h2>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Body -->
        <div class="mt-5 space-y-5">
          <!-- Video Section -->
          <div v-if="exercise.video_url" class="rounded-2xl overflow-hidden bg-slate-950 border border-slate-800">
            <div v-if="youtubeEmbedUrl" class="aspect-video w-full">
              <iframe 
                :src="youtubeEmbedUrl" 
                class="w-full h-full" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
              ></iframe>
            </div>
            <div v-else class="p-4 flex items-center justify-between">
              <span class="text-xs text-slate-400 truncate max-w-xs">📹 {{ exercise.video_url }}</span>
              <a 
                :href="exercise.video_url" 
                target="_blank" 
                class="px-3 py-1 text-xs font-semibold text-emerald-400 bg-emerald-500/10 rounded-lg hover:bg-emerald-500/20"
              >
                Abrir Vídeo ↗
              </a>
            </div>
          </div>

          <!-- Detalhes do Exercício -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-slate-500 block mb-1">Equipamento Requerido</span>
              <span class="text-slate-200 font-semibold">{{ exercise.equipment || 'Nenhum / Peso Corporal' }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
              <span class="text-slate-500 block mb-1">Origem do Cadastro</span>
              <span class="text-slate-200 font-semibold">{{ exercise.is_custom ? 'Minha Consultoria' : 'Biblioteca Padrão' }}</span>
            </div>
          </div>

          <!-- Instruções Técnicas -->
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-xs">
            <span class="text-slate-400 font-semibold block mb-2">Instruções de Execução & Postura:</span>
            <p class="text-slate-300 leading-relaxed whitespace-pre-wrap">
              {{ exercise.description || 'Nenhuma instrução técnica adicional cadastrada para este exercício.' }}
            </p>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-5 mt-5 border-t border-slate-800">
          <div>
            <button
              v-if="exercise.is_custom && canManage"
              @click="archive"
              :disabled="archiving"
              class="px-3 py-2 text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition-colors"
            >
              {{ archiving ? 'Arquivando...' : 'Arquivar Exercício' }}
            </button>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="exercise.is_custom && canManage"
              @click="emit('edit', exercise)"
              class="px-4 py-2 text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-colors"
            >
              Editar Exercício
            </button>
            <button
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-colors"
            >
              Fechar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Exercise } from '../types'
import { exerciseService } from '../services/exerciseService'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  isOpen: boolean
  exercise?: Exercise | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'edit', exercise: Exercise): void
  (e: 'archived'): void
}>()

const authStore = useAuthStore()
const archiving = ref(false)

const canManage = computed(() => {
  const role = authStore.activeRole
  return role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER' || role === 'TRAINER'
})

const youtubeEmbedUrl = computed(() => {
  if (!props.exercise?.video_url) return null
  const url = props.exercise.video_url
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/)
  return match ? `https://www.youtube.com/embed/${match[1]}` : null
})

function close() {
  emit('close')
}

async function archive() {
  if (!props.exercise) return
  if (!confirm(`Deseja realmente arquivar o exercício "${props.exercise.name}"?`)) return

  archiving.value = true
  try {
    await exerciseService.archive(props.exercise.id)
    emit('archived')
    close()
  } catch (err: any) {
    alert(err.message || 'Erro ao arquivar exercício.')
  } finally {
    archiving.value = false
  }
}

function formatMuscleGroup(mg: string): string {
  const map: Record<string, string> = {
    CHEST: 'Peito',
    BACK: 'Costas',
    LEGS: 'Pernas',
    SHOULDERS: 'Ombros',
    BICEPS: 'Bíceps',
    TRICEPS: 'Tríceps',
    ABDOMINALS: 'Abdômen',
    CORE: 'Core',
    CARDIO: 'Cardio',
    FULL_BODY: 'Corpo Inteiro',
  }
  return map[mg] || mg
}
</script>
