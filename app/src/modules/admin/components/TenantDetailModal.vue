<template>
  <div v-if="isOpen && tenant" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle transition-all transform bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl z-10 text-slate-100">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>🏢</span> Detalhes da Organização
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ tenant.name }}</p>
          </div>
          <button @click="close" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
            ✕
          </button>
        </div>

        <div class="mt-4 space-y-4 text-xs">
          <!-- Status & Tipo -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Tipo de Tenant</span>
              <strong class="text-white text-sm font-semibold">{{ tenant.type }}</strong>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Status Atual</span>
              <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full border inline-block mt-0.5"
                :class="tenant.status === 'ACTIVE'
                  ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                  : 'bg-rose-500/10 text-rose-400 border-rose-500/20'"
              >
                {{ tenant.status }}
              </span>
            </div>
          </div>

          <!-- Documento & Criação -->
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
            <div class="flex justify-between">
              <span class="text-slate-400">Documento / CNPJ:</span>
              <span class="text-slate-200 font-mono">{{ tenant.document || 'Não informado' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Criado em:</span>
              <span class="text-slate-200 font-mono">{{ formatDate(tenant.created_at) }}</span>
            </div>
          </div>

          <!-- Alunos & Treinadores -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Alunos Ativos</span>
              <strong class="text-white text-base font-bold font-mono">{{ tenant.active_students }}</strong>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Plano Atual</span>
              <span class="text-indigo-400 font-bold block text-sm mt-0.5">
                {{ tenant.current_plan?.name || 'Sem plano' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-slate-800">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl transition-all"
          >
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AdminTenantItem } from '../types'

defineProps<{
  isOpen: boolean
  tenant: AdminTenantItem | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function close() {
  emit('close')
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}
</script>
