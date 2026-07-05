<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '~/stores/auth';
import { usePosStore } from '~/stores/pos';
import { useSettingsStore } from '~/stores/settings';
import { useApi } from '~/composables/useApi';
import { Lock, Unlock, Loader2, CheckCircle2, X, Moon, Sun } from 'lucide-vue-next';
import { useToast } from '~/composables/useToast';

const authStore = useAuthStore();
const posStore = usePosStore();
const settingsStore = useSettingsStore();
const api = useApi();
const { toast, show: showToast } = useToast();

const currentTime = ref('');
let timer;

onMounted(() => {
  const updateTime = () => {
    currentTime.value = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
  };
  updateTime();
  timer = setInterval(updateTime, 60000);
});

onUnmounted(() => {
  clearInterval(timer);
});

const password = ref('');
const isUnlocking = ref(false);

const handleUnlock = async () => {
  if (!password.value) return;
  
  isUnlocking.value = true;
  try {
    await api('/auth/verify-password', {
      method: 'POST',
      body: { password: password.value }
    });
    posStore.unlockPos();
    password.value = '';
    showToast("Kassa ochildi", "success");
  } catch (e) {
    showToast("Parol xato!", "error");
    password.value = '';
  } finally {
    isUnlocking.value = false;
  }
};
</script>

<template>
  <div class="h-screen w-full bg-gray-50 dark:bg-gray-900 flex flex-col overflow-hidden relative transition-colors duration-200">
    <!-- Top Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-6 shrink-0 transition-colors duration-200">
      <div class="flex items-center gap-2 sm:gap-4">
        <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white">Universal POS</h1>
        <NuxtLink v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'manager' || authStore.user?.role === 'cashier'" to="/admin" class="ml-2 sm:ml-4 px-2 sm:px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs sm:text-sm font-medium transition-colors flex items-center gap-1 sm:gap-2">
          ⚙️ <span class="hidden sm:inline">Admin Panel</span>
        </NuxtLink>
      </div>
      <div class="flex items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
        <div class="hidden md:block"><span class="font-medium text-gray-900 dark:text-white">Kassir:</span> {{ authStore.user?.name || 'Yuklanmoqda...' }}</div>
        <div class="hidden sm:block">{{ currentTime }}</div>
        <div class="hidden sm:block w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
        <button @click="settingsStore.toggleTheme()" class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-gray-700 rounded-full transition-colors flex items-center justify-center w-8 h-8" title="Tungi rejim">
          <Moon v-if="settingsStore.theme === 'light'" class="w-4 h-4" />
          <Sun v-else class="w-4 h-4" />
        </button>
        <button @click="posStore.lockPos()" title="Kassani qulflash" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors flex items-center gap-2">
          <Lock class="w-4 h-4" />
          <span class="font-medium hidden sm:inline">Yopish</span>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex overflow-hidden">
      <slot />
    </main>

    <!-- Lock Screen Overlay -->
    <ClientOnly>
      <div v-if="posStore.isLocked" class="fixed inset-0 z-[100] bg-gray-900/95 flex flex-col items-center justify-center backdrop-blur-md">
        <div class="bg-white rounded-3xl p-10 max-w-sm w-full shadow-2xl flex flex-col items-center text-center">
          <div class="w-20 h-20 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center mb-6 shadow-inner">
            <Lock class="w-10 h-10" />
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Kassa Yopiq</h2>
          <p class="text-gray-500 mb-8">Kassani ochish uchun parolingizni kiriting <br><span class="font-medium text-gray-700">({{ authStore.user?.name }})</span></p>
          
          <form @submit.prevent="handleUnlock" class="w-full flex flex-col gap-4">
            <input 
              v-model="password" 
              type="password" 
              placeholder="Parol..." 
              class="w-full p-4 border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:outline-none text-center text-xl tracking-widest font-mono transition-colors"
              required
            >
            <button 
              type="submit" 
              :disabled="isUnlocking"
              class="w-full bg-primary-600 hover:bg-primary-700 disabled:opacity-70 text-white font-medium py-4 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 text-lg shadow-sm"
            >
              <Loader2 v-if="isUnlocking" class="w-6 h-6 animate-spin" />
              <Unlock v-else class="w-6 h-6" />
              Ochish
            </button>
          </form>
        </div>
      </div>
    </ClientOnly>

    <!-- Global Toast Notification -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="transform translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 sm:translate-x-0"
      leave-to-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    >
      <div v-if="toast" class="fixed bottom-4 right-4 z-[110] max-w-sm w-full bg-white shadow-lg rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
        <div class="p-4 flex items-start gap-3">
          <div class="shrink-0 pt-0.5">
            <CheckCircle2 v-if="toast.type === 'success'" class="h-6 w-6 text-emerald-500" />
            <X v-else class="h-6 w-6 text-red-500 bg-red-100 rounded-full p-1" />
          </div>
          <div class="w-0 flex-1">
            <p class="text-sm font-medium text-gray-900">{{ toast.type === 'success' ? 'Muvaffaqiyatli!' : 'Xatolik' }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ toast.message }}</p>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>
