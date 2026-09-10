<template>
  <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
    <!-- Header do Dia -->
    <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-800">
      <div class="flex items-center gap-3 flex-1">
        <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center">
          {{ dayIndex + 1 }}
        </span>
        <input 
          v-model="day.name" 
          type="text" 
          placeholder="Ex: Treino A - Peito e Tríceps"
          class="px-3 py-1.5 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white font-bold text-sm focus:outline-none focus:border-emerald-500 flex-1 max-w-sm"
        />
      </div>

      <div class="flex items-center gap-2">
        <button 
          type="button"
          @click="$emit('remove-day')"
          title="Remover este dia"
          class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors text-xs"
        >
          🗑️ Excluir Dia
        </button>
      </div>
    </div>

    <!-- Lista de Exercícios Adicionados -->
    <div v-if="day.exercises.length === 0" class="py-6 text-center text-slate-500 text-xs border border-dashed border-slate-800 rounded-xl">
      Nenhum exercício adicionado a este dia ainda.
    </div>

    <div v-else class="space-y-3">
      <div 
        v-for="(ex, exIdx) in day.exercises" 
        :key="exIdx"
        class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-xs space-y-3"
      >
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2 font-semibold text-white">
            <span class="text-slate-500 font-mono">{{ exIdx + 1 }}.</span>
            <span>{{ ex.name || 'Exercício selecionado' }}</span>
          </div>
          <button 
            type="button" 
            @click="removeExercise(exIdx)"
            class="text-slate-500 hover:text-rose-400 transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Parâmetros da Série -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
          <div>
            <label class="block text-[10px] text-slate-400 mb-0.5">Séries</label>
            <input 
              v-model.number="ex.sets" 
              type="number" 
              min="1" 
              class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-center focus:outline-none focus:border-emerald-500" 
            />
          </div>

          <div>
            <label class="block text-[10px] text-slate-400 mb-0.5">Repetições</label>
            <input 
              v-model="ex.reps" 
              type="text" 
              placeholder="Ex: 8-10" 
              class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-center focus:outline-none focus:border-emerald-500" 
            />
          </div>

          <div>
            <label class="block text-[10px] text-slate-400 mb-0.5">Carga Inicial</label>
            <input 
              v-model="ex.weight" 
              type="text" 
              placeholder="Ex: 24kg" 
              class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-center focus:outline-none focus:border-emerald-500" 
            />
          </div>

          <div>
            <label class="block text-[10px] text-slate-400 mb-0.5">Descanso (seg)</label>
            <input 
              v-model.number="ex.rest_seconds" 
              type="number" 
              placeholder="Ex: 60" 
              class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-center focus:outline-none focus:border-emerald-500" 
            />
          </div>

          <div>
            <label class="block text-[10px] text-slate-400 mb-0.5">RPE (Esforço)</label>
            <input 
              v-model="ex.rpe" 
              type="text" 
              placeholder="Ex: 8 ou 9" 
              class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-center focus:outline-none focus:border-emerald-500" 
            />
          </div>
        </div>

        <div>
          <input 
            v-model="ex.notes" 
            type="text" 
            placeholder="Instrução técnica (Ex: Pausa de 1s embaixo, pico de contração...)" 
            class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-slate-300 placeholder-slate-500 text-[11px] focus:outline-none focus:border-emerald-500" 
          />
        </div>
      </div>
    </div>

    <!-- Botão de Adicionar Exercício -->
    <button 
      type="button"
      @click="$emit('open-exercise-picker', dayIndex)"
      class="w-full py-2.5 border border-dashed border-slate-700 hover:border-emerald-500/50 hover:bg-emerald-500/5 text-emerald-400 rounded-xl text-xs font-semibold transition-all flex items-center justify-center gap-1.5"
    >
      <span>+</span>
      <span>Adicionar Exercício da Biblioteca</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { TemplateDayItem } from '../types'

const props = defineProps<{
  day: TemplateDayItem
  dayIndex: number
}>()

const emit = defineEmits<{
  (e: 'remove-day'): void
  (e: 'open-exercise-picker', dayIndex: number): void
}>()

function removeExercise(index: number) {
  props.day.exercises.splice(index, 1)
}
</script>
