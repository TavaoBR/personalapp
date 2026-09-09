<script setup lang="ts">
import { useThemeStore } from '@/stores/theme';
import { onMounted } from 'vue';

const themeStore = useThemeStore();

onMounted(() => {
  themeStore.applyTheme();
});
</script>

<template>
  <RouterView />
</template>
