<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '~/composables/useApi';
import { TrendingUp, Users, Package, DollarSign } from 'lucide-vue-next';

definePageMeta({
  layout: 'admin'
});

const stats = ref([
  { title: 'Bugungi tushum', value: '0', icon: DollarSign, color: 'text-emerald-600', bg: 'bg-emerald-100' },
  { title: 'Sotilgan mahsulotlar', value: '0', icon: TrendingUp, color: 'text-blue-600', bg: 'bg-blue-100' },
  { title: 'Jami Mijozlar', value: '0', icon: Users, color: 'text-purple-600', bg: 'bg-purple-100' },
  { title: 'Ombordagi mahsulotlar', value: '0', icon: Package, color: 'text-orange-600', bg: 'bg-orange-100' },
]);

onMounted(async () => {
  const api = useApi();
  try {
    const res = await api('/reports/daily');
    stats.value[0].value = res.total_revenue?.toLocaleString() + " so'm" || '0';
    stats.value[1].value = res.sales_count?.toString() || '0';
  } catch(e) {
    console.error(e);
  }
});
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Bosh Sahifa</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="stat in stats" :key="stat.title" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
        <div :class="['w-12 h-12 rounded-lg flex items-center justify-center shrink-0', stat.bg, stat.color]">
          <component :is="stat.icon" class="w-6 h-6" />
        </div>
        <div>
          <div class="text-sm text-gray-500 font-medium">{{ stat.title }}</div>
          <div class="text-2xl font-bold text-gray-900 mt-1">{{ stat.value }}</div>
        </div>
      </div>
    </div>

    <!-- Charts / Recent Activity placeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Savdo Tahlili</h3>
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center text-gray-400 border border-dashed border-gray-200">
          Grafik tez orada qo'shiladi...
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">So'nggi Tranzaksiyalar</h3>
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center text-gray-400 border border-dashed border-gray-200">
          Ro'yxat tez orada qo'shiladi...
        </div>
      </div>
    </div>
  </div>
</template>
