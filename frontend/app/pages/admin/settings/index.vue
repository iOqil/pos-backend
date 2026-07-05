<script setup>
import { ref } from 'vue';
import { Store, Printer, Bell, Shield, CheckCircle2, Eye, EyeOff } from 'lucide-vue-next';
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'admin' });

const authStore = useAuthStore();
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);

const securityForm = ref({
  currentPassword: '',
  newPassword: ''
});

const activeTab = ref('general');

const form = ref({
  storeName: 'Mening Do\'konim',
  address: 'Toshkent sh., Chilonzor tumani',
  phone: '+998 90 123 45 67',
  printerIp: '192.168.1.87',
  printerPort: '9100',
  receiptHeader: 'Xaridingiz uchun rahmat!',
  receiptFooter: 'Murojaat uchun: +998 90 123 45 67'
});

const isSaving = ref(false);

const saveSettings = () => {
  isSaving.value = true;
  setTimeout(() => {
    isSaving.value = false;
    alert("Sozlamalar muvaffaqiyatli saqlandi!");
  }, 800);
};
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tizim Sozlamalari</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Tabs -->
      <div class="space-y-2">
        <button @click="activeTab = 'general'" :class="['w-full text-left px-4 py-3 font-medium rounded-xl flex items-center gap-3 transition-colors', activeTab === 'general' ? 'bg-white border border-primary-500 text-primary-700 shadow-sm' : 'bg-transparent text-gray-600 hover:bg-gray-100']">
          <Store class="w-5 h-5" /> Asosiy Sozlamalar
        </button>
        <button @click="activeTab = 'printer'" :class="['w-full text-left px-4 py-3 font-medium rounded-xl flex items-center gap-3 transition-colors', activeTab === 'printer' ? 'bg-white border border-primary-500 text-primary-700 shadow-sm' : 'bg-transparent text-gray-600 hover:bg-gray-100']">
          <Printer class="w-5 h-5" /> Printer (Xprinter)
        </button>
        <button @click="activeTab = 'notifications'" :class="['w-full text-left px-4 py-3 font-medium rounded-xl flex items-center gap-3 transition-colors', activeTab === 'notifications' ? 'bg-white border border-primary-500 text-primary-700 shadow-sm' : 'bg-transparent text-gray-600 hover:bg-gray-100']">
          <Bell class="w-5 h-5" /> Bildirishnomalar
        </button>
        <button @click="activeTab = 'security'" :class="['w-full text-left px-4 py-3 font-medium rounded-xl flex items-center gap-3 transition-colors', activeTab === 'security' ? 'bg-white border border-primary-500 text-primary-700 shadow-sm' : 'bg-transparent text-gray-600 hover:bg-gray-100']">
          <Shield class="w-5 h-5" /> Xavfsizlik
        </button>
      </div>

      <!-- Settings Form -->
      <div class="lg:col-span-2">
        <form @submit.prevent="saveSettings" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 flex flex-col gap-6">
          
          <div v-if="activeTab === 'general'" class="pb-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><Store class="w-5 h-5 text-gray-500"/> Do'kon ma'lumotlari</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Do'kon nomi</label>
                <input v-model="form.storeName" type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefon raqam</label>
                <input v-model="form.phone" type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Manzil</label>
                <input v-model="form.address" type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'printer'" class="pb-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><Printer class="w-5 h-5 text-gray-500"/> Chek va Printer sozlamalari</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Printer IP manzili (LAN)</label>
                <input v-model="form.printerIp" type="text" placeholder="192.168.1.x" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Printer Port</label>
                <input v-model="form.printerPort" type="text" placeholder="9100" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chek tepasidagi yozuv</label>
                <input v-model="form.receiptHeader" type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Chek tagidagi yozuv</label>
                <input v-model="form.receiptFooter" type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'notifications'" class="pb-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><Bell class="w-5 h-5 text-gray-500"/> Bildirishnomalar</h3>
            <p class="text-sm text-gray-500">Bildirishnomalar funksiyasi tez orada qo'shiladi...</p>
          </div>

          <div v-if="activeTab === 'security'" class="pb-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><Shield class="w-5 h-5 text-gray-500"/> Xavfsizlik va Parol</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Joriy parol</label>
                <div class="relative">
                  <input v-model="securityForm.currentPassword" :type="showCurrentPassword ? 'text' : 'password'" placeholder="********" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none pr-10">
                  <button type="button" @click="showCurrentPassword = !showCurrentPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <Eye v-if="!showCurrentPassword" class="h-5 w-5" />
                    <EyeOff v-else class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Yangi parol</label>
                <div class="relative">
                  <input v-model="securityForm.newPassword" :type="showNewPassword ? 'text' : 'password'" placeholder="Yangi parol..." class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none pr-10">
                  <button type="button" @click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <Eye v-if="!showNewPassword" class="h-5 w-5" />
                    <EyeOff v-else class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>
            
          </div>

          <div class="flex justify-end pt-4 border-t border-gray-100 mt-auto">
            <button type="submit" :disabled="isSaving" class="px-6 py-3 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
              <CheckCircle2 v-if="!isSaving" class="w-5 h-5" />
              {{ isSaving ? 'Saqlanmoqda...' : 'Saqlash' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
