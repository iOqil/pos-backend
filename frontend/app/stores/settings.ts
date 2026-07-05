import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useSettingsStore = defineStore('settings', () => {
  const theme = ref('light'); // 'light' or 'dark'
  const posViewMode = ref('grid'); // 'grid' or 'list'

  if (import.meta.client) {
    const savedTheme = localStorage.getItem('pos_theme');
    if (savedTheme === 'dark' || savedTheme === 'light') {
      theme.value = savedTheme;
    }

    const savedViewMode = localStorage.getItem('pos_view_mode');
    if (savedViewMode === 'grid' || savedViewMode === 'list') {
      posViewMode.value = savedViewMode;
    }
  }

  watch(theme, (newTheme) => {
    if (import.meta.client) {
      localStorage.setItem('pos_theme', newTheme);
      applyTheme(newTheme);
    }
  });

  watch(posViewMode, (newMode) => {
    if (import.meta.client) {
      localStorage.setItem('pos_view_mode', newMode);
    }
  });

  const applyTheme = (currentTheme) => {
    if (import.meta.client) {
      if (currentTheme === 'dark') {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      theme.value = currentTheme;
      localStorage.setItem('pos_theme', currentTheme);
    }
  };

  const toggleTheme = () => {
    if (import.meta.client) {
      const isDark = document.documentElement.classList.contains('dark');
      applyTheme(isDark ? 'light' : 'dark');
    }
  };

  const initTheme = () => {
    applyTheme(theme.value);
  };

  return {
    theme,
    posViewMode,
    toggleTheme,
    initTheme
  };
});
