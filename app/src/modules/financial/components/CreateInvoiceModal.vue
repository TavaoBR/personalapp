<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>🧾</span> Emitir Cobrança / Fatura
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Lance uma cobrança de mensalidade ou serviço avulso.</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
          <!-- Aluno (seleção ou fixo) -->
          <div v-if="!fixedStudentId">
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Aluno *
            </label>
            <select
              v-model="form.student_id"
              required
              class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            >
              <option value="" disabled>Selecione o aluno...</option>
              <option v-for="s in studentsList" :key="s.id" :value="s.id">
                {{ s.name }} ({{ s.email }})
              </option>
            </select>
          </div>
          <div v-else class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300">
            Aluno: <strong class="text-white">{{ studentName || 'Aluno Selecionado' }}</strong>
          </div>

          <!-- Valor e Vencimento -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Valor a Cobrar (R$) *
              </label>
              <div class="relative">
                <span class="absolute left-3.5 top-2.5 text-sm text-slate-500">R$</span>
                <input
                  v-model.number="form.amount"
                  type="number"
                  step="0.01"
                  min="1"
                  required
                  placeholder="200.00"
                  class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-emerald-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                Data de Vencimento *
              </label>
              <input
                v-model="form.due_date"
                type="date"
                required
                class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <!-- Período de Referência -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Mês / Período de Referência
            </label>
            <input
              v-model="form.reference_period"
              type="text"
              placeholder="Ex: 2026-09 ou Mensalidade Setembro"
              class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Observações / Descrição -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
              Observações
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Ex: Mensalidade da consultoria de treinos e acompanhamento presencial."
              class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500"
            ></textarea>
          </div>

          <div v-if="errorMessage" class="p-3 text-xs text-rose-400 bg-rose-500/10 border border-rose-500/20 rounded-xl">
            {{ errorMessage }}
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl transition-all"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 rounded-xl shadow-lg shadow-emerald-500/20 transition-all font-display"
            >
              {{ submitting ? 'Emitindo...' : 'Emitir Cobrança' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { financialService } from '../services/financialService'
import { studentService } from '@/modules/students/services/studentService'

const props = defineProps<{
  isOpen: boolean
  fixedStudentId?: string
  studentName?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'created'): void
}>()

const submitting = ref(false)
const errorMessage = ref('')
const studentsList = ref<Array<{ id: string; name: string; email: string }>>([])

const defaultDueDate = () => {
  const d = new Date()
  d.setDate(d.getDate() + 5)
  return d.toISOString().split('T')[0]
}

const form = reactive({
  student_id: props.fixedStudentId || '',
  amount: 200,
  due_date: defaultDueDate(),
  reference_period: new Date().toISOString().substring(0, 7),
  notes: ''
})

watch(() => props.fixedStudentId, (val) => {
  if (val) form.student_id = val
})

watch(() => props.isOpen, async (open) => {
  if (open) {
    errorMessage.value = ''
    if (!props.fixedStudentId && studentsList.value.length === 0) {
      loadStudents()
    }
  }
})

async function loadStudents() {
  try {
    const list = await studentService.list()
    studentsList.value = list.map(s => ({
      id: s.id,
      name: s.name,
      email: s.email
    }))
  } catch (err) {
    console.error('Erro ao listar alunos:', err)
  }
}

function close() {
  errorMessage.value = ''
  emit('close')
}

async function handleSubmit() {
  const targetStudentId = props.fixedStudentId || form.student_id
  if (!targetStudentId) {
    errorMessage.value = 'Selecione um aluno.'
    return
  }

  submitting.value = true
  errorMessage.value = ''
  try {
    await financialService.createPayment({
      student_id: targetStudentId,
      amount: Number(form.amount),
      due_date: form.due_date,
      reference_period: form.reference_period.trim() || undefined,
      notes: form.notes.trim() || undefined
    })

    form.notes = ''
    emit('created')
    close()
  } catch (err: any) {
    errorMessage.value = err.message || 'Erro ao emitir cobrança.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (props.isOpen && !props.fixedStudentId) {
    loadStudents()
  }
})
</script>
