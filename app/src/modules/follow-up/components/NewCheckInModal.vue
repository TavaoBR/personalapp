<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>📅</span> Novo Check-in Periódico
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Aluno: <span class="text-white font-semibold">{{ studentName }}</span></p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Data do Check-in -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Data do Check-in *
            </label>
            <input
              v-model="form.check_in_date"
              type="date"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
            />
          </div>

          <!-- Escalas 1 a 5: Energia, Sono, Dor Muscular, Dieta -->
          <div class="space-y-3 pt-2">
            <!-- 1. Energia -->
            <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-white flex items-center gap-1.5">
                  <span>⚡</span> Disposição & Energia
                </span>
                <span class="font-bold text-amber-400">{{ form.energy_level }}/5</span>
              </div>
              <div class="grid grid-cols-5 gap-1.5">
                <button
                  v-for="v in [1, 2, 3, 4, 5]"
                  :key="v"
                  type="button"
                  @click="form.energy_level = v"
                  class="py-1.5 text-xs font-bold rounded-lg border transition-all"
                  :class="form.energy_level === v 
                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 font-mono' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  {{ v }}
                </button>
              </div>
            </div>

            <!-- 2. Qualidade do Sono -->
            <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-white flex items-center gap-1.5">
                  <span>😴</span> Qualidade do Sono
                </span>
                <span class="font-bold text-indigo-400">{{ form.sleep_quality }}/5</span>
              </div>
              <div class="grid grid-cols-5 gap-1.5">
                <button
                  v-for="v in [1, 2, 3, 4, 5]"
                  :key="v"
                  type="button"
                  @click="form.sleep_quality = v"
                  class="py-1.5 text-xs font-bold rounded-lg border transition-all"
                  :class="form.sleep_quality === v 
                    ? 'bg-indigo-500/20 border-indigo-500 text-indigo-300 font-mono' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  {{ v }}
                </button>
              </div>
            </div>

            <!-- 3. Dores Musculares / Recuperação -->
            <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-white flex items-center gap-1.5">
                  <span>🔥</span> Dor Muscular / Fadiga
                </span>
                <span class="font-bold text-rose-400">{{ form.soreness_level }}/5</span>
              </div>
              <div class="grid grid-cols-5 gap-1.5">
                <button
                  v-for="v in [1, 2, 3, 4, 5]"
                  :key="v"
                  type="button"
                  @click="form.soreness_level = v"
                  class="py-1.5 text-xs font-bold rounded-lg border transition-all"
                  :class="form.soreness_level === v 
                    ? 'bg-rose-500/20 border-rose-500 text-rose-300 font-mono' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  {{ v }}
                </button>
              </div>
            </div>

            <!-- 4. Adesão à Dieta -->
            <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-white flex items-center gap-1.5">
                  <span>🥗</span> Adesão à Dieta / Hidratação
                </span>
                <span class="font-bold text-emerald-400">{{ form.diet_adherence }}/5</span>
              </div>
              <div class="grid grid-cols-5 gap-1.5">
                <button
                  v-for="v in [1, 2, 3, 4, 5]"
                  :key="v"
                  type="button"
                  @click="form.diet_adherence = v"
                  class="py-1.5 text-xs font-bold rounded-lg border transition-all"
                  :class="form.diet_adherence === v 
                    ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 font-mono' 
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white'"
                >
                  {{ v }}
                </button>
              </div>
            </div>
          </div>

          <!-- Observações do Aluno -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Comentários, Dores ou Dúvidas da Semana
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Ex: Treinos fluíram bem, porém senti leve desconforto no ombro direito durante o supino..."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500 resize-none"
            ></textarea>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl transition-all font-display shadow-lg shadow-blue-600/20"
            >
              {{ saving ? 'Registrando...' : 'Registrar Check-in' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { followUpService } from '../services/followUpService'

const props = defineProps<{
  isOpen: boolean
  studentId: string
  studentName: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const saving = ref(false)
const errorMessage = ref('')

const form = reactive({
  check_in_date: new Date().toISOString().split('T')[0],
  energy_level: 4,
  sleep_quality: 4,
  soreness_level: 2,
  diet_adherence: 4,
  notes: ''
})

function close() {
  emit('close')
}

async function handleSubmit() {
  saving.value = true
  errorMessage.value = ''
  try {
    await followUpService.createCheckIn(props.studentId, {
      check_in_date: form.check_in_date,
      energy_level: form.energy_level,
      sleep_quality: form.sleep_quality,
      soreness_level: form.soreness_level,
      diet_adherence: form.diet_adherence,
      notes: form.notes || undefined
    })
    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao registrar check-in.'
  } finally {
    saving.value = false
  }
}
</script>
