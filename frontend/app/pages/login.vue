<script setup>
import { ref } from 'vue';
import { useAuthStore } from '~/stores/auth';
import { navigateTo } from '#app';
import { LogIn, Eye, EyeOff } from 'lucide-vue-next';

definePageMeta({
  layout: false
});

const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const errorMsg = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
  isLoading.value = true;
  errorMsg.value = '';
  
  const success = await authStore.login({
    email: email.value,
    password: password.value
  });
  
  if (success) {
    navigateTo('/admin');
  } else {
    errorMsg.value = 'Email yoki parol noto\'g\'ri';
  }
  isLoading.value = false;
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="mx-auto h-12 w-12 bg-primary-600 rounded-xl flex items-center justify-center shadow-lg">
        <LogIn class="w-6 h-6 text-white" />
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        Tizimga kirish
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Universal POS boshqaruv paneli
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
        <form class="space-y-6" @submit.prevent="handleLogin">
          
          <div v-if="errorMsg" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm text-center font-medium">
            {{ errorMsg }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email manzili</label>
            <div class="mt-1">
              <input v-model="email" type="email" placeholder="admin@example.com" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Parol</label>
            <div class="mt-1 relative">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="********" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm pr-10">
              <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                <Eye v-if="!showPassword" class="h-5 w-5" />
                <EyeOff v-else class="h-5 w-5" />
              </button>
            </div>
          </div>

          <div>
            <button type="submit" :disabled="isLoading" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors disabled:opacity-50">
              <span v-if="isLoading">Kirilmoqda...</span>
              <span v-else>Kirish</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
