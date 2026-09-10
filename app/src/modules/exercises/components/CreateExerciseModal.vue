<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <!-- Modal Panel -->
      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl z-10 text-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm">
              💪
            </span>
            {{ editingExercise ? 'Editar Exercício' : 'Novo Exercício Personalizado' }}
          </h3>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
            ✕
          </button>
        </div>

        <form @submit.prevent="submit" class="mt-5 space-y-4">
          <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
            {{ errorMessage }}
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Nome do Exercício *</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              placeholder="Ex: Supino Inclinado com Halteres"
              class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Grupo Muscular *</label>
              <select 
                v-model="form.muscle_group" 
                required
                class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-emerald-500"
              >
                <option value="CHEST">Peito (Chest)</option>
                <option value="BACK">Costas (Back)</option>
                <option value="LEGS">Pernas (Legs)</option>
                <option value="SHOULDERS">Ombros (Shoulders)</option>
                <option value="BICEPS">Bíceps (Biceps)</option>
                <option value="TRICEPS">Tríceps (Triceps)</option>
                <option value="ABDOMINALS">Abdômen (Abdominals)</option>
                <option value="CORE">Core (Lombar/Centro)</option>
                <option value="CARDIO">Cardio</option>
                <option value="FULL_BODY">Corpo Inteiro (Full Body)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Equipamento Utilizado</label>
              <input 
                v-model="form.equipment" 
                type="text" 
                placeholder="Ex: Halteres, Barra, Banco 45°"
                class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Link de Vídeo Demonstrativo (URL)</label>
            <input 
              v-model="form.video_url" 
              type="url" 
              placeholder="https://youtube.com/watch?v=..."
              class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Orientações de Execução / Descrição</label>
            <textarea 
              v-model="form.description" 
              rows="3"
              placeholder="Postura, amplitude, cadência e pontos de atenção durante o movimento..."
              class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500"
            ></textarea>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="loading"
              class="px-5 py-2 text-xs font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 disabled:opacity-50 transition-all flex items-center gap-2"
            >
              <span v-if="loading" class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
              {{ loading ? 'Salvando...' : (editingExercise ? 'Salvar Alterações' : 'Criar Exercício') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { exerciseService } from '../services/exerciseService'
import type { Exercise, MuscleGroup } from '../types'

const props = defineProps<{
  isOpen: boolean
  editingExercise?: Exercise | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  name: '',
  muscle_group: 'CHEST' as MuscleGroup,
  equipment: '',
  description: '',
  video_url: '',
})

watch(() => props.isOpen, (open) => {
  if (open) {
    errorMessage.value = ''
    if (props.editingExercise) {
      form.name = props.editingExercise.name
      form.muscle_group = props.editingExercise.muscle_group
      form.equipment = props.editingExercise.equipment || ''
      form.description = props.editingExercise.description || ''
      form.video_url = props.editingExercise.video_url || ''
    } else {
      form.name = ''
      form.muscle_group = 'CHEST'
      form.equipment = ''
      form.description = ''
      form.video_url = ''
    }
  }
})

function close() {
  emit('close')
}

async function submit() {
  loading.value = true
  errorMessage.value = ''

  try {
    if (props.editingExercise) {
      await exerciseService.update(props.editingExercise.id, {
        name: form.name,
        muscle_group: form.muscle_group,
        equipment: form.equipment || null,
        description: form.description || null,
        video_url: form.video_url || null,
      })
    } else {
      await exerciseService.create({
        name: form.name,
        muscle_group: form.muscle_group,
        equipment: form.equipment || undefined,
        description: form.description || undefined,
        video_url: form.video_url || undefined,
      })
    }

    emit('saved')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao salvar exercício.'
  } finally {
    loading.value = false
  }
}
</script>
