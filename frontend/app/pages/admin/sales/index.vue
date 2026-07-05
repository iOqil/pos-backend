<script setup>
import { ref, onMounted, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { ChevronRight, ChevronDown, Package } from 'lucide-vue-next';

definePageMeta({ layout: 'admin' });

const api = useApi();
const sales = ref([]);
const isLoading = ref(true);

const searchQuery = ref('');
const perPage = ref(15);
const currentPage = ref(1);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const expandedRows = ref(new Set());

const toggleRow = (id) => {
  const newSet = new Set(expandedRows.value);
  if (newSet.has(id)) {
    newSet.delete(id);
  } else {
    newSet.add(id);
  }
  expandedRows.value = newSet;
};

let searchTimeout = null;

const fetchSales = async () => {
  isLoading.value = true;
  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      per_page: perPage.value,
      page: currentPage.value
    });
    const res = await api(`/sales?${params.toString()}`);
    if (res.data) {
      sales.value = res.data;
      pagination.value = {
        current_page: res.current_page,
        last_page: res.last_page,
        total: res.total
      };
    } else {
      sales.value = res;
    }
  } catch(e) {
    console.error(e);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchSales();
});

watch([searchQuery, perPage], () => {
  currentPage.value = 1;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchSales();
  }, 300);
});

const formatCurrency = (val) => parseInt(val).toLocaleString() + " so'm";
const formatDate = (dateString) => new Date(dateString).toLocaleString('uz-UZ');
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Savdolar Tarixi</h1>
    </div>

    <!-- Toolbar -->
    <div class="bg-white p-4 rounded-t-xl border border-gray-200 border-b-0 flex flex-col md:flex-row md:flex-wrap gap-4 items-start md:items-center justify-between">
      <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto flex-1 max-w-2xl">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Chek raqami (SALE-...) yoki kassir izlash..." 
          class="w-full sm:flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500"
        >
      </div>
      <div class="flex items-center gap-2 text-sm text-gray-600 w-full md:w-auto justify-end">
        <span>Ko'rsatish:</span>
        <select v-model="perPage" class="px-2 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 bg-white">
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-b-xl overflow-hidden shadow-sm flex flex-col">
      <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-medium">
          <tr>
            <th class="px-6 py-4 w-12"></th>
            <th class="px-6 py-4">Sotuv ID</th>
            <th class="px-6 py-4">Kassir</th>
            <th class="px-6 py-4">Sana</th>
            <th class="px-6 py-4">To'lov Turi</th>
            <th class="px-6 py-4 text-right">Jami Summa</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="isLoading">
            <td colspan="6" class="px-6 py-8 text-center text-gray-400">Yuklanmoqda...</td>
          </tr>
          <template v-for="sale in sales" :key="sale.id">
            <tr class="hover:bg-gray-50 transition-colors cursor-pointer group" @click="toggleRow(sale.id)">
              <td class="px-6 py-4 text-gray-400 group-hover:text-primary-600 transition-colors">
                <ChevronDown v-if="expandedRows.has(sale.id)" class="w-5 h-5" />
                <ChevronRight v-else class="w-5 h-5" />
              </td>
              <td class="px-6 py-4 font-medium text-gray-900">{{ sale.sale_number || '#' + sale.id }}</td>
              <td class="px-6 py-4">{{ sale.cashier?.name || 'Admin' }}</td>
              <td class="px-6 py-4">{{ formatDate(sale.created_at) }}</td>
              <td class="px-6 py-4">
                <span :class="['px-2.5 py-1 rounded-full text-xs font-medium uppercase', sale.payments?.[0]?.method === 'cash' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800']">
                  {{ sale.payments?.[0]?.method || 'nomalum' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right font-bold text-gray-900">{{ formatCurrency(sale.total) }}</td>
            </tr>
            <!-- Expanded Details -->
            <tr v-if="expandedRows.has(sale.id)" class="bg-gray-50/50">
              <td colspan="6" class="px-6 py-6 border-t border-gray-100">
                <div class="pl-8">
                  <h4 class="text-sm font-bold text-gray-700 flex items-center gap-2 mb-4">
                    <Package class="w-4 h-4 text-gray-500" />
                    Sotilgan Mahsulotlar ({{ sale.items?.length || 0 }})
                  </h4>
                  <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm flex flex-col">
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                      <thead class="bg-gray-50 text-gray-700 font-medium border-b border-gray-200">
                        <tr>
                          <th class="px-4 py-2">Mahsulot Nomi</th>
                          <th class="px-4 py-2 text-center">Soni</th>
                          <th class="px-4 py-2 text-right">Narxi</th>
                          <th class="px-4 py-2 text-right">Jami</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in sale.items" :key="item.id" class="hover:bg-gray-50">
                          <td class="px-4 py-2 font-medium text-gray-900">
                            {{ item.product_name }}
                            <div class="text-xs text-gray-500 mt-0.5">{{ item.product_barcode || 'Barcode yo\'q' }}</div>
                          </td>
                          <td class="px-4 py-2 text-center">
                            <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-xs font-medium">{{ item.quantity }} dona</span>
                          </td>
                          <td class="px-4 py-2 text-right">{{ formatCurrency(item.unit_price) }}</td>
                          <td class="px-4 py-2 text-right font-semibold text-gray-900">{{ formatCurrency(item.total) }}</td>
                        </tr>
                        <tr v-if="!sale.items || sale.items.length === 0">
                          <td colspan="4" class="px-4 py-4 text-center text-gray-500 text-sm">Mahsulotlar topilmadi.</td>
                        </tr>
                      </tbody>
                    </table>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!isLoading && sales.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Hech qanday savdo topilmadi.</td>
          </tr>
        </tbody>
      </table>
      </div>
      
      <!-- Pagination Controls -->
      <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
        <span class="text-sm text-gray-600">Sahifa {{ pagination.current_page }} / {{ pagination.last_page }} (Jami: {{ pagination.total }})</span>
        <div class="flex gap-2">
          <button 
            @click="currentPage--; fetchSales()" 
            :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Oldingi
          </button>
          <button 
            @click="currentPage++; fetchSales()" 
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Keyingi
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
