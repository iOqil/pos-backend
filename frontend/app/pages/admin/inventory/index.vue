<script setup>
import { ref, onMounted, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { useProductStore } from '~/stores/product';
import { useAuthStore } from '~/stores/auth';
import { PackagePlus, PackageMinus, X, CheckCircle2, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next';

definePageMeta({ layout: 'admin' });

const api = useApi();
const productStore = useProductStore();
const authStore = useAuthStore();
const movements = ref([]);
const isLoading = ref(true);

const searchQuery = ref('');
const perPage = ref(20);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const showModal = ref(false);
const isSaving = ref(false);
const form = ref({
  product_id: '',
  type: 'purchase',
  quantity: null,
  note: ''
});

const loadMovements = async (page = 1) => {
  isLoading.value = true;
  try {
    const res = await api(`/inventory?page=${page}&per_page=${perPage.value}&search=${searchQuery.value}`);
    movements.value = res.data || res;
    if (res.current_page) {
      pagination.value = {
        current_page: res.current_page,
        last_page: res.last_page,
        total: res.total
      };
    }
  } catch(e) {
    console.error(e);
  } finally {
    isLoading.value = false;
  }
};

watch([searchQuery, perPage], () => loadMovements(1));

onMounted(async () => {
  await loadMovements();
  if (productStore.products.length === 0) {
    await productStore.fetchProducts();
  }
});

const openModal = (type) => {
  form.value = { product_id: '', type: type, quantity: null, note: '' };
  showModal.value = true;
};

const saveMovement = async () => {
  isSaving.value = true;
  try {
    let payload = { ...form.value };
    // Adjust quantity based on type
    if (['write_off', 'return'].includes(payload.type)) {
      payload.quantity = -Math.abs(payload.quantity);
    } else {
      payload.quantity = Math.abs(payload.quantity);
    }
    
    await api('/inventory', { method: 'POST', body: payload });
    showModal.value = false;
    await loadMovements();
    await productStore.fetchProducts(); // Refresh stock
  } catch(e) {
    alert("Xatolik: " + (e.data?.message || 'Zaxira yetarli emas yoki xato'));
  } finally {
    isSaving.value = false;
  }
};

const formatType = (type) => {
  const types = {
    'purchase': { label: 'Kirim (Xarid)', color: 'bg-emerald-100 text-emerald-800' },
    'sale': { label: 'Sotuv', color: 'bg-blue-100 text-blue-800' },
    'return': { label: 'Vozvrat (Qaytarish)', color: 'bg-orange-100 text-orange-800' },
    'adjustment': { label: 'To\'g\'rilash', color: 'bg-gray-100 text-gray-800' },
    'write_off': { label: 'Brak/Spisaniya', color: 'bg-red-100 text-red-800' }
  };
  return types[type] || { label: type, color: 'bg-gray-100 text-gray-800' };
};
</script>

<template>
  <div>
    <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-900 w-full sm:w-auto">Inventarizatsiya (Zaxira)</h1>
      <div v-if="authStore.user?.role === 'admin'" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <button @click="openModal('purchase')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors w-full sm:w-auto">
          <PackagePlus class="w-5 h-5" /> Tovar kirim qilish
        </button>
        <button @click="openModal('write_off')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors w-full sm:w-auto">
          <PackageMinus class="w-5 h-5" /> Brak qilish
        </button>
      </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
        <div class="relative w-full sm:max-w-xs">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Qidirish (Nomi, Barcode)..." 
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none"
          >
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
          <span class="text-sm text-gray-600">Ko'rsatish:</span>
          <select v-model="perPage" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-medium">
          <tr>
            <th class="px-6 py-4">Sana</th>
            <th class="px-6 py-4">Mahsulot</th>
            <th class="px-6 py-4">Harakat turi</th>
            <th class="px-6 py-4">Miqdor</th>
            <th class="px-6 py-4">Izoh</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="isLoading">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">Yuklanmoqda...</td>
          </tr>
          <tr v-for="movement in movements" :key="movement.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">{{ new Date(movement.created_at).toLocaleString('ru-RU') }}</td>
            <td class="px-6 py-4 font-medium text-gray-900">{{ movement.product?.name || 'Noma\'lum' }}</td>
            <td class="px-6 py-4">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', formatType(movement.type).color]">
                {{ formatType(movement.type).label }}
              </span>
            </td>
            <td class="px-6 py-4 font-bold" :class="movement.quantity > 0 ? 'text-emerald-600' : 'text-red-600'">
              {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
            </td>
            <td class="px-6 py-4 text-gray-500">{{ movement.note || '-' }}</td>
          </tr>
          <tr v-if="!isLoading && movements.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Zaxira harakatlari topilmadi.</td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-600 hidden sm:block">
          Jami: <span class="font-medium">{{ pagination.total }}</span> ta operatsiya
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
          <button 
            @click="loadMovements(pagination.current_page - 1)" 
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            <ChevronLeft class="w-4 h-4" /> Oldingi
          </button>
          <span class="text-sm font-medium px-4">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button 
            @click="loadMovements(pagination.current_page + 1)" 
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            Keyingi <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
          <h2 class="text-xl font-bold text-gray-800">
            {{ form.type === 'purchase' ? 'Tovar kirim qilish' : 'Tovarni brak/spisaniya qilish' }}
          </h2>
          <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveMovement" class="p-6 flex flex-col gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mahsulotni tanlang</label>
            <select v-model="form.product_id" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              <option value="" disabled>-- Tanlang --</option>
              <option v-for="prod in productStore.products" :key="prod.id" :value="prod.id">
                {{ prod.name }} (Qoldiq: {{ prod.stock_quantity }})
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Miqdori (soni)</label>
            <input v-model="form.quantity" required type="number" min="1" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Izoh (ixtiyoriy)</label>
            <textarea v-model="form.note" rows="2" placeholder="Nima uchun?" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none"></textarea>
          </div>
          
          <div class="flex gap-3 mt-4">
            <button type="button" @click="showModal = false" class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
              Bekor qilish
            </button>
            <button type="submit" :disabled="isSaving" :class="['flex-1 px-4 py-3 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2', form.type === 'purchase' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700', isSaving ? 'opacity-50' : '']">
              <CheckCircle2 v-if="!isSaving" class="w-5 h-5" /> {{ isSaving ? 'Saqlanmoqda...' : 'Tasdiqlash' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
