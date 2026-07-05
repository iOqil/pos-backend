<script setup>
import { ref, onMounted, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { Plus, Edit2, Trash2, X, CheckCircle2, Search, ChevronLeft, ChevronRight, Eye, EyeOff } from 'lucide-vue-next';
import { useToast } from '~/composables/useToast';

definePageMeta({ layout: 'admin' });

const { show: showToast } = useToast();

const users = ref([]);
const isLoading = ref(true);
const api = useApi();

const searchQuery = ref('');
const perPage = ref(15);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const fetchUsers = async (page = 1) => {
  isLoading.value = true;
  try {
    const res = await api(`/users?page=${page}&per_page=${perPage.value}&search=${searchQuery.value}`);
    users.value = res.data || res;
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

watch([searchQuery, perPage], () => fetchUsers(1));

onMounted(() => fetchUsers());

const showModal = ref(false);
const isEditing = ref(false);
const form = ref({ id: null, name: '', email: '', password: '', role: 'cashier' });
const showPassword = ref(false);

const openCreate = () => {
  form.value = { id: null, name: '', email: '', password: '', role: 'cashier' };
  isEditing.value = false;
  showModal.value = true;
};

const openEdit = (user) => {
  form.value = { id: user.id, name: user.name, email: user.email, password: '', role: user.role || 'cashier' };
  isEditing.value = true;
  showModal.value = true;
};

const saveUser = async () => {
  try {
    if (isEditing.value) {
      const payload = { ...form.value };
      if (!payload.password) delete payload.password; // Don't send empty password
      await api(`/users/${form.value.id}`, { method: 'PUT', body: payload });
    } else {
      await api('/users', { method: 'POST', body: form.value });
    }
    showModal.value = false;
    showToast("Foydalanuvchi muvaffaqiyatli saqlandi", "success");
    await fetchUsers();
  } catch(e) {
    showToast(e.data?.message || 'Email band yoki noto\'g\'ri format', "error");
  }
};

const deleteUser = async (id) => {
  if (!confirm("O'chirishni tasdiqlaysizmi?")) return;
  try {
    await api(`/users/${id}`, { method: 'DELETE' });
    showToast("Foydalanuvchi o'chirildi", "success");
    await fetchUsers();
  } catch(e) {
    showToast("Xatolik yuz berdi", "error");
  }
};
</script>

<template>
  <div>
    <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-900 w-full sm:w-auto">Foydalanuvchilar (Xodimlar)</h1>
      <button @click="openCreate" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors w-full sm:w-auto">
        <Plus class="w-5 h-5" /> Xodim qo'shish
      </button>
    </div>

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

      <!-- Table Wrapper -->
      <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-medium">
          <tr>
            <th class="px-6 py-4">F.I.SH</th>
            <th class="px-6 py-4">Email (Login)</th>
            <th class="px-6 py-4">Rol</th>
            <th class="px-6 py-4 text-center">Amallar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="isLoading">
            <td colspan="4" class="px-6 py-8 text-center text-gray-400">Yuklanmoqda...</td>
          </tr>
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-6 py-4">{{ user.email }}</td>
            <td class="px-6 py-4">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium uppercase', user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800']">
                {{ user.role || 'Kassir' }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openEdit(user)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"><Edit2 class="w-4 h-4" /></button>
                <button @click="deleteUser(user.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"><Trash2 class="w-4 h-4" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!isLoading && users.length === 0">
            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Hech qanday foydalanuvchi topilmadi.</td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-600 hidden sm:block">
          Jami: <span class="font-medium">{{ pagination.total }}</span> ta xodim
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
          <button 
            @click="fetchUsers(pagination.current_page - 1)" 
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            <ChevronLeft class="w-4 h-4" /> Oldingi
          </button>
          <span class="text-sm font-medium px-4">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button 
            @click="fetchUsers(pagination.current_page + 1)" 
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 flex items-center gap-1"
          >
            Keyingi <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
          <h2 class="text-xl font-bold text-gray-800">{{ isEditing ? 'Xodimni tahrirlash' : 'Yangi xodim' }}</h2>
          <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveUser" class="p-6 flex flex-col gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To'liq ismi</label>
            <input v-model="form.name" required type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email (Login)</label>
            <input v-model="form.email" required type="email" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Parol {{ isEditing ? '(O\'zgartirish uchun)' : '' }}</label>
            <div class="relative">
              <input v-model="form.password" :required="!isEditing" :type="showPassword ? 'text' : 'password'" placeholder="********" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none pr-10">
              <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                <Eye v-if="!showPassword" class="h-5 w-5" />
                <EyeOff v-else class="h-5 w-5" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
            <select v-model="form.role" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
              <option value="cashier">Kassir</option>
              <option value="admin">Admin</option>
            </select>
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
