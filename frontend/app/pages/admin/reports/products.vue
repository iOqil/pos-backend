<script setup>
import { ref, onMounted, watch } from 'vue';
import { Package, Calendar, Clock, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { useApi } from '~/composables/useApi';

definePageMeta({ layout: 'admin' });

const api = useApi();
const loading = ref(true);
const productsReport = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const searchQuery = ref('');
const perPage = ref(20);

const fetchReport = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api(`/reports/products?page=${page}&per_page=${perPage.value}&search=${searchQuery.value}`);
    productsReport.value = res.data;
    if (res.current_page) {
      pagination.value = {
        current_page: res.current_page,
        last_page: res.last_page,
        total: res.total
      };
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

watch([searchQuery, perPage], () => fetchReport(1));

onMounted(() => {
  fetchReport();
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('uz-UZ', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="w-full sm:w-auto">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Batafsil Mahsulotlar Hisoboti</h1>
        <p class="text-sm text-gray-500 mt-1">Har bir mahsulot qachon va qanchadan sotilganini kuzating.</p>
      </div>
      <button @click="fetchReport(1)" class="w-full sm:w-auto bg-primary-50 text-primary-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-100 transition-colors">
        Yangilash
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
        <div class="relative w-full sm:max-w-xs">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Qidirish..." 
            class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none"
          >
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
          <span class="text-sm text-gray-600 dark:text-gray-400">Ko'rsatish:</span>
          <select v-model="perPage" class="border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
          <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700 uppercase text-xs">
            <tr>
              <th class="px-6 py-4 font-semibold">Mahsulot</th>
              <th class="px-6 py-4 font-semibold">Barcode</th>
              <th class="px-6 py-4 font-semibold">Narxi</th>
              <th class="px-6 py-4 font-semibold">Chegirma</th>
              <th class="px-6 py-4 font-semibold">Soni</th>
              <th class="px-6 py-4 font-semibold">Jami Summa</th>
              <th class="px-6 py-4 font-semibold">Sotilgan Vaqt</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                Yuklanmoqda...
              </td>
            </tr>
            <tr v-else-if="productsReport.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center gap-2">
                  <Package class="w-8 h-8 text-gray-300" />
                  Ma'lumot topilmadi
                </div>
              </td>
            </tr>
            <tr v-else v-for="item in productsReport" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900 dark:text-white">{{ item.product_name }}</div>
                <div class="text-xs text-gray-500">Chek: {{ item.sale?.sale_number }}</div>
              </td>
              <td class="px-6 py-4">{{ item.product_barcode || '-' }}</td>
              <td class="px-6 py-4">{{ parseInt(item.unit_price).toLocaleString() }} so'm</td>
              <td class="px-6 py-4 text-red-500">{{ item.discount > 0 ? '-' + parseInt(item.discount).toLocaleString() : '0' }}</td>
              <td class="px-6 py-4 font-medium">
                <span class="bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded text-gray-700 dark:text-gray-200">
                  {{ item.quantity }} dona
                </span>
              </td>
              <td class="px-6 py-4 font-bold text-primary-600 dark:text-primary-400">{{ parseInt(item.total).toLocaleString() }} so'm</td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1 text-xs">
                  <span class="flex items-center gap-1"><Calendar class="w-3 h-3 text-gray-400" /> {{ formatDate(item.created_at) }}</span>
                  <span class="flex items-center gap-1 text-gray-500"><Clock class="w-3 h-3 text-gray-400" /> {{ formatTime(item.created_at) }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
          Jami: <span class="font-medium text-gray-900 dark:text-white">{{ pagination.total }}</span> ta savdo
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
          <button 
            @click="fetchReport(pagination.current_page - 1)" 
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors flex items-center gap-1 dark:text-white"
          >
            <ChevronLeft class="w-4 h-4" /> Oldingi
          </button>
          <span class="text-sm font-medium px-4 dark:text-gray-300">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button 
            @click="fetchReport(pagination.current_page + 1)" 
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors flex items-center gap-1 dark:text-white"
          >
            Keyingi <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
