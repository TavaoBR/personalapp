<template>
  <div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>Equipe & Treinadores</span>
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
            {{ trainers.length }} profissionais
          </span>
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          Gerencie a equipe técnica da sua assessoria, CREF e especialidades.
        </p>
      </div>

      <button
        v-if="canManageTrainers"
        @click="isCreateModalOpen = true"
        class="px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2"
      >
        <span class="text-base leading-none">+</span>
        Cadastrar Treinador
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-400">
      <div class="w-10 h-10 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-sm">Carregando equipe de treinadores...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-6 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
      <p class="text-rose-400 font-medium">{{ errorMessage }}</p>
      <button @click="loadTrainers" class="mt-3 px-4 py-2 text-xs bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 rounded-lg">
        Tentar Novamente
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="trainers.length === 0" class="p-12 rounded-2xl bg-slate-900/40 border border-slate-800 text-center">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4">
        🏋️‍♂️
      </div>
      <h3 class="text-lg font-semibold text-white">Nenhum treinador cadastrado</h3>
      <p class="text-sm text-slate-400 max-w-md mx-auto mt-1">
        Sua equipe ainda não possui treinadores adicionados.
      </p>
      <button 
        v-if="canManageTrainers"
        @click="isCreateModalOpen = true" 
        class="mt-4 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl"
      >
        Cadastrar Primeiro Treinador
      </button>
    </div>

    <!-- Trainers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div 
        v-for="trainer in trainers" 
        :key="trainer.id"
        class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-all shadow-lg hover:shadow-xl flex flex-col justify-between"
      >
        <div>
          <!-- Header Card -->
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-bold text-base flex items-center justify-center shadow-md shadow-indigo-500/20">
                {{ getInitials(trainer.user.name) }}
              </div>
              <div>
                <h3 class="font-bold text-white text-base leading-tight">{{ trainer.user.name }}</h3>
                <span class="text-xs text-indigo-400 font-mono mt-0.5 block">
                  {{ trainer.cref || 'Sem registro CREF' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Bio -->
          <p v-if="trainer.bio" class="text-xs text-slate-300 line-clamp-2 mb-4 italic">
            "{{ trainer.bio }}"
          </p>

          <!-- Specialties Badges -->
          <div v-if="trainer.specialties && trainer.specialties.length > 0" class="flex flex-wrap gap-1.5 mb-4">
            <span 
              v-for="(spec, i) in trainer.specialties" 
              :key="i"
              class="px-2 py-0.5 text-[11px] font-medium rounded-md bg-slate-800 text-slate-300 border border-slate-700"
            >
              {{ spec }}
            </span>
          </div>
        </div>

        <!-- Contact Footer -->
        <div class="pt-4 border-t border-slate-800/80 text-xs text-slate-400 space-y-1.5">
          <div class="flex items-center gap-2">
            <span>✉️</span>
            <span class="truncate">{{ trainer.user.email }}</span>
          </div>
          <div v-if="trainer.user.phone" class="flex items-center gap-2">
            <span>📱</span>
            <span>{{ trainer.user.phone }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cadastro de Treinador -->
    <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="isCreateModalOpen = false"></div>

        <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl z-10 text-slate-100">
          <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
              <span>🏋️‍♂️</span> Cadastrar Novo Treinador
            </h3>
            <button @click="isCreateModalOpen = false" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">✕</button>
          </div>

          <form @submit.prevent="submitTrainer" class="mt-4 space-y-4">
            <div v-if="createError" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
              {{ createError }}
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Nome Completo *</label>
              <input v-model="form.name" required type="text" placeholder="Ex: Roberto Lima" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500" />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">E-mail Profissional *</label>
              <input v-model="form.email" required type="email" placeholder="treinador@assessoria.com" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Telefone</label>
                <input v-model="form.phone" type="tel" placeholder="(11) 98888-9999" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Registro CREF</label>
                <input v-model="form.cref" type="text" placeholder="Ex: 012345-G/SP" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Especialidades (separadas por vírgula)</label>
              <input v-model="specialtiesInput" type="text" placeholder="Musculação, LPO, Emagrecimento, Corrida" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500" />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Biografia / Apresentação</label>
              <textarea v-model="form.bio" rows="2" placeholder="Experiência profissional e foco de atuação..." class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
              <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-xl">Cancelar</button>
              <button type="submit" :disabled="creating" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl disabled:opacity-50 flex items-center gap-2">
                <span v-if="creating" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ creating ? 'Cadastrando...' : 'Confirmar Cadastro' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { trainerService } from '../services/trainerService'
import type { TrainerProfile } from '../types'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const trainers = ref<TrainerProfile[]>([])
const loading = ref(true)
const errorMessage = ref('')

const isCreateModalOpen = ref(false)
const creating = ref(false)
const createError = ref('')
const specialtiesInput = ref('')

const form = reactive({
  name: '',
  email: '',
  phone: '',
  cref: '',
  bio: '',
})

const canManageTrainers = computed(() => {
  const role = authStore.activeRole
  return role === 'OWNER' || role === 'ADMIN' || role === 'MANAGER'
})

async function loadTrainers() {
  loading.value = true
  errorMessage.value = ''
  try {
    trainers.value = await trainerService.list()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao carregar treinadores.'
  } finally {
    loading.value = false
  }
}

async function submitTrainer() {
  creating.value = true
  createError.value = ''

  const specialties = specialtiesInput.value
    .split(',')
    .map(s => s.trim())
    .filter(s => s.length > 0)

  try {
    await trainerService.create({
      name: form.name,
      email: form.email,
      phone: form.phone || undefined,
      cref: form.cref || undefined,
      specialties,
      bio: form.bio || undefined,
    })

    isCreateModalOpen.value = false
    form.name = ''
    form.email = ''
    form.phone = ''
    form.cref = ''
    form.bio = ''
    specialtiesInput.value = ''
    await loadTrainers()
  } catch (err: any) {
    createError.value = err.message || 'Erro ao cadastrar treinador.'
  } finally {
    creating.value = false
  }
}

function getInitials(name: string): string {
  if (!name) return 'T'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

onMounted(() => {
  loadTrainers()
})
</script>
