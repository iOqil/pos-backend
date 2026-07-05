<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '~/composables/useApi';
import { TrendingUp, Calendar, CreditCard, ShoppingBag } from 'lucide-vue-next';

definePageMeta({ layout: 'admin' });

const dailyRevenue = ref(0);
const salesCount = ref(0);
const isLoading = ref(true);

onMounted(async () => {
  const api = useApi();
  try {
    const today = new Date().toISOString().split('T')[0];
    const res = await api(`/reports/daily?date=${today}`);
    dailyRevenue.value = res.total_revenue || 0;
    salesCount.value = res.sales_count || 0;
  } catch(e) {
    console.error(e);
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Hisobotlar</h1>

    <div v-if="isLoading" class="text-gray-500 mb-6">Yuklanmoqda...</div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
        <div class="p-4 bg-emerald-100 text-emerald-600 rounded-full">
          <TrendingUp class="w-6 h-6" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Bugungi tushum</p>
          <h3 class="text-2xl font-bold text-gray-900">{{ parseInt(dailyRevenue).toLocaleString() }} <span class="text-lg">so'm</span></h3>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
        <div class="p-4 bg-blue-100 text-blue-600 rounded-full">
          <ShoppingBag class="w-6 h-6" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Bugungi savdolar</p>
          <h3 class="text-2xl font-bold text-gray-900">{{ salesCount }} <span class="text-lg">ta</span></h3>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
        <div class="p-4 bg-purple-100 text-purple-600 rounded-full">
          <CreditCard class="w-6 h-6" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">O'rtacha chek</p>
          <h3 class="text-2xl font-bold text-gray-900">{{ salesCount > 0 ? parseInt(dailyRevenue / salesCount).toLocaleString() : 0 }} <span class="text-lg">so'm</span></h3>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
        <div class="p-4 bg-orange-100 text-orange-600 rounded-full">
          <Calendar class="w-6 h-6" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Haftalik o'sish</p>
          <h3 class="text-2xl font-bold text-gray-900">+12%</h3>
        </div>
      </div>
    </div>

    <!-- Charts area (Mocked for PRO UI feel) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6">Haftalik Tushum (Grafik)</h3>
        
        <div class="flex items-end gap-2 h-64 w-full">
          <!-- Mock Chart Bars -->
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-100 rounded-t-sm" style="height: 40%"></div>
            <span class="text-xs text-gray-500">Dush</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-200 rounded-t-sm" style="height: 60%"></div>
            <span class="text-xs text-gray-500">Sesh</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-300 rounded-t-sm" style="height: 35%"></div>
            <span class="text-xs text-gray-500">Chor</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-400 rounded-t-sm" style="height: 80%"></div>
            <span class="text-xs text-gray-500">Pay</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-500 rounded-t-sm" style="height: 90%"></div>
            <span class="text-xs text-gray-500">Juma</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-primary-500 rounded-t-sm shadow-lg" style="height: 100%"></div>
            <span class="text-xs text-gray-800 font-bold">Shan</span>
          </div>
          <div class="flex-1 flex flex-col justify-end items-center gap-2">
            <div class="w-full bg-blue-100 rounded-t-sm" style="height: 20%"></div>
            <span class="text-xs text-gray-500">Yak</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Eng ko'p sotilganlar</h3>
        <ul class="space-y-4">
          <li class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center font-bold text-gray-500">1</div>
              <div>
                <p class="font-medium text-gray-900">A4 Qog'oz (Svetocopy)</p>
                <p class="text-xs text-gray-500">Kanstovar</p>
              </div>
            </div>
            <span class="font-bold text-gray-900">45 ta</span>
          </li>
          <li class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center font-bold text-gray-500">2</div>
              <div>
                <p class="font-medium text-gray-900">O'tkan Kunlar</p>
                <p class="text-xs text-gray-500">Kitoblar</p>
              </div>
            </div>
            <span class="font-bold text-gray-900">32 ta</span>
          </li>
          <li class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center font-bold text-gray-500">3</div>
              <div>
                <p class="font-medium text-gray-900">Qora ruchka (Linc)</p>
                <p class="text-xs text-gray-500">Kanstovar</p>
              </div>
            </div>
            <span class="font-bold text-gray-900">28 ta</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
