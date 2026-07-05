<script setup>
import { ref, onMounted, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { Plus, Edit2, Trash2, X, CheckCircle2, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next';

definePageMeta({ layout: 'admin' });

const categories = ref([]);
const isLoading = ref(true);
const api = useApi();

const searchQuery = ref('');
const perPage = ref(15);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const fetchCategories = async (page = 1) => {
  isLoading.value = true;
  try {
    const res = await api(`/categories?page=${page}&per_page=${perPage.value}&search=${searchQuery.value}`);
    categories.value = res.data || res;
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

watch([searchQuery, perPage], () => fetchCategories(1));

onMounted(() => fetchCategories());

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const form = ref({ id: null, name: '' });

const openCreate = () => {
  form.value = { id: null, name: '' };
  isEditing.value = false;
  showModal.value = true;
};

const openEdit = (cat) => {
  form.value = { id: cat.id, name: cat.name };
  isEditing.value = true;
  showModal.value = true;
};

const saveCategory = async () => {
  try {
    if (isEditing.value) {
      await api(`/categories/${form.value.id}`, {
        method: 'PUT',
        body: { name: form.value.name }
      });
    } else {
      await api('/categories', {
        method: 'POST',
        body: { name: form.value.name }
      });
    }
    showModal.value = false;
    await fetchCategories();
  } catch(e) {
    alert("Xatolik yuz berdi: " + (e.data?.message || 'Noma\'lum xato'));
  }
};

const deleteCategory = async (id) => {
  if (!confirm("Haqiqatan ham o'chirmoqchimisiz?")) return;
  try {
    await api(`/categories/${id}`, { method: 'DELETE' });
    await fetchCategories();
  } catch(e) {
    alert("O'chirishda xatolik yuz berdi. Balki bu kategoriya mahsulotlarga bog'langandir.");
  }
};
</script>

<template>
  <div>
    <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-900 w-full sm:w-auto">Kategoriyalar</h1>
      <button @click="openCreate" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors w-full sm:w-auto">
        <Plus class="w-5 h-5" />
        Kategoriya qo'shish
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
        <div class="relative w-full sm:max-w-xs">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Qidirish..." 
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none"
          >
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
          <span class="text-sm text-gray-600">Ko'rsatish:</span>
          <select v-model="perPage" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-medium">
          <tr>
            <th class="px-6 py-4">ID</th>
            <th class="px-6 py-4">Nomi</th>
            <th class="px-6 py-4 text-center">Amallar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="isLoading">
            <td colspan="3" class="px-6 py-8 text-center text-gray-400">Yuklanmoqda...</td>
          </tr>
          <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">#{{ cat.id }}</td>
            <td class="px-6 py-4">{{ cat.name }}</td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openEdit(cat)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Tahrirlash"><Edit2 class="w-4 h-4" /></button>
                <button @click="deleteCategory(cat.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" title="O'chirish"><Trash2 class="w-4 h-4" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!isLoading && categories.length === 0">
            <td colspan="3" class="px-6 py-8 text-center text-gray-500">Kategoriyalar yo'q.</td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-600 hidden sm:block">
          Jami: <span class="font-medium">{{ pagination.total }}</span> ta kategoriya
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
          <button 
            @click="fetchCategories(pagination.current_page - 1)" 
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            <ChevronLeft class="w-4 h-4" /> Oldingi
          </button>
          <span class="text-sm font-medium px-4">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button 
            @click="fetchCategories(pagination.current_page + 1)" 
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            Keyingi <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
          <h2 class="text-xl font-bold text-gray-800">{{ isEditing ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya' }}</h2>
          <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveCategory" class="p-6 flex flex-col gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomi</label>
            <input v-model="form.name" required type="text" class="w-full text-lg p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none" placeholder="Masalan: Kitoblar">
          </div>
          
          <div class="flex gap-3 mt-4">
            <button type="button" @click="showModal = false" class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
              Bekor qilish
            </button>
            <button type="submit" class="flex-1 px-4 py-3 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors flex items-center justify-center gap-2">
              <CheckCircle2 class="w-5 h-5" /> Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
