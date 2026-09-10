<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <!-- Backdrop -->
      <div 
        class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" 
        @click="close"
      ></div>

      <!-- Modal Panel -->
      <div class="relative inline-block w-full max-w-2xl p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-base">
                👤
              </span>
              Matricular Novo Aluno
            </h3>
            <p class="text-sm text-slate-400 mt-1">
              Cadastre o aluno e vincule a um treinador da sua assessoria.
            </p>
          </div>
          <button 
            @click="close" 
            class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submit" class="mt-6 space-y-6">
          <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
            {{ errorMessage }}
          </div>

          <!-- Seção 1: Dados Pessoais -->
          <div>
            <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">
              1. Dados Cadastrais
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-300 mb-1">Nome Completo *</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  placeholder="Ex: Carlos Silva"
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">E-mail *</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  placeholder="aluno@exemplo.com"
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Telefone / WhatsApp</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  placeholder="(11) 99999-8888"
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Data de Nascimento</label>
                <input 
                  v-model="form.birth_date" 
                  type="date" 
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Gênero</label>
                <select 
                  v-model="form.gender" 
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                >
                  <option value="">Não informar</option>
                  <option value="M">Masculino</option>
                  <option value="F">Feminino</option>
                  <option value="OUTRO">Outro</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Seção 2: Atribuição de Personal -->
          <div>
            <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">
              2. Treinador Responsável
            </h4>
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Selecionar Treinador da Equipe</label>
              <select 
                v-model="form.trainer_id" 
                class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
              >
                <option value="">Nenhum (Sem atribuição inicial)</option>
                <option v-for="t in trainers" :key="t.id" :value="t.id">
                  {{ t.user.name }} ({{ t.cref || 'Sem CREF' }})
                </option>
              </select>
            </div>
          </div>

          <!-- Seção 3: Contato de Emergência e Notas -->
          <div>
            <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">
              3. Contato de Emergência e Observações
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Nome do Contato de Emergência</label>
                <input 
                  v-model="form.emergency_contact_name" 
                  type="text" 
                  placeholder="Ex: Esposa(o), Pai/Mãe"
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Telefone de Emergência</label>
                <input 
                  v-model="form.emergency_contact_phone" 
                  type="tel" 
                  placeholder="(11) 98888-7777"
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-300 mb-1">Observações Iniciais</label>
                <textarea 
                  v-model="form.notes" 
                  rows="2"
                  placeholder="Lesões prévias, metas específicas ou preferências..."
                  class="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="loading"
              class="px-5 py-2.5 text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 disabled:opacity-50 transition-all flex items-center gap-2"
            >
              <span v-if="loading" class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
              {{ loading ? 'Matriculando...' : 'Confirmar Matrícula' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { studentService } from '../services/studentService'
import { trainerService } from '@/modules/trainers/services/trainerService'
import type { TrainerProfile } from '@/modules/trainers/types'

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'enrolled'): void
}>()

const loading = ref(false)
const errorMessage = ref('')
const trainers = ref<TrainerProfile[]>([])

const form = reactive({
  name: '',
  email: '',
  phone: '',
  birth_date: '',
  gender: '',
  trainer_id: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  notes: '',
})

async function fetchTrainers() {
  try {
    trainers.value = await trainerService.list()
  } catch (err) {
    console.error('Erro ao carregar treinadores:', err)
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    errorMessage.value = ''
    form.name = ''
    form.email = ''
    form.phone = ''
    form.birth_date = ''
    form.gender = ''
    form.trainer_id = ''
    form.emergency_contact_name = ''
    form.emergency_contact_phone = ''
    form.notes = ''
    fetchTrainers()
  }
})

onMounted(() => {
  if (props.isOpen) {
    fetchTrainers()
  }
})

function close() {
  emit('close')
}

async function submit() {
  loading.value = true
  errorMessage.value = ''

  try {
    await studentService.enroll({
      name: form.name,
      email: form.email,
      phone: form.phone || undefined,
      birth_date: form.birth_date || undefined,
      gender: form.gender || undefined,
      trainer_id: form.trainer_id || undefined,
      emergency_contact_name: form.emergency_contact_name || undefined,
      emergency_contact_phone: form.emergency_contact_phone || undefined,
      notes: form.notes || undefined,
    })

    emit('enrolled')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Falha ao matricular aluno. Verifique os dados ou limites de plano.'
  } finally {
    loading.value = false
  }
}
</script>
