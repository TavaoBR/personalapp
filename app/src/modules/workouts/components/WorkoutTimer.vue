<template>
  <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md flex flex-wrap items-center justify-between gap-4">
    <!-- Duração Total do Treino -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
        ⏱️
      </div>
      <div>
        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 block">Tempo de Treino</span>
        <span class="text-xl font-black font-mono text-white tracking-wider">{{ formattedElapsedTime }}</span>
      </div>
    </div>

    <!-- Timer de Descanso Ativo -->
    <div v-if="restTimeRemaining > 0" class="flex items-center gap-4 bg-slate-950/80 px-4 py-2 rounded-xl border border-emerald-500/30">
      <div>
        <span class="text-[10px] uppercase font-bold text-emerald-400 block animate-pulse">Descanso</span>
        <span class="text-lg font-black font-mono text-emerald-300">{{ restTimeRemaining }}s</span>
      </div>
      <button 
        @click="cancelRestTimer"
        class="text-[11px] text-slate-400 hover:text-white px-2 py-1 bg-slate-800 rounded-lg transition-colors"
      >
        Pular
      </button>
    </div>

    <!-- Quick Rest Presets -->
    <div class="flex items-center gap-1.5 text-xs">
      <span class="text-[10px] text-slate-500 mr-1 hidden sm:inline">Iniciar Descanso:</span>
      <button 
        v-for="sec in [45, 60, 90, 120]" 
        :key="sec"
        @click="startRest(sec)"
        class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-mono text-xs transition-colors"
      >
        {{ sec }}s
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  startedAt: string
}>()

const elapsedSeconds = ref(0)
const restTimeRemaining = ref(0)
let workoutInterval: any = null
let restInterval: any = null

const formattedElapsedTime = computed(() => {
  const m = Math.floor(elapsedSeconds.value / 60)
  const s = elapsedSeconds.value % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

function updateElapsed() {
  const start = new Date(props.startedAt).getTime()
  const now = Date.now()
  elapsedSeconds.value = Math.max(0, Math.floor((now - start) / 1000))
}

function startRest(seconds: number) {
  clearInterval(restInterval)
  restTimeRemaining.value = seconds
  restInterval = setInterval(() => {
    if (restTimeRemaining.value > 0) {
      restTimeRemaining.value--
    } else {
      clearInterval(restInterval)
    }
  }, 1000)
}

function cancelRestTimer() {
  clearInterval(restInterval)
  restTimeRemaining.value = 0
}

defineExpose({
  startRest,
})

onMounted(() => {
  updateElapsed()
  workoutInterval = setInterval(updateElapsed, 1000)
})

onUnmounted(() => {
  clearInterval(workoutInterval)
  clearInterval(restInterval)
})
</script>
