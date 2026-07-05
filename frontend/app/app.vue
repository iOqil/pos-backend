<script setup>
import { onMounted } from 'vue';
import { useSettingsStore } from '~/stores/settings';

onMounted(() => {
  const settingsStore = useSettingsStore();
  settingsStore.initTheme();
});
</script>

<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>
