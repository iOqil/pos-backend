<script setup>
import { 
  LayoutDashboard, 
  Package, 
  Tags, 
  Users, 
  Settings, 
  Menu, 
  X, 
  BarChart2, 
  LogOut,
  ShoppingCart,
  Barcode,
  Briefcase,
  PackageSearch,
  MonitorPlay,
  CheckCircle2
} from 'lucide-vue-next';
import { useAuthStore } from '~/stores/auth';
import { useToast } from '~/composables/useToast';
import { ref } from 'vue';

const isMobileMenuOpen = ref(false);

const authStore = useAuthStore();
const { toast } = useToast();

const navigation = [
  { name: 'Kassa (POS)', href: '/pos', icon: MonitorPlay },
  { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
  { name: 'Inventarizatsiya', href: '/admin/inventory', icon: PackageSearch },
  { name: 'Mahsulotlar', href: '/admin/products', icon: Package },
  { name: 'E-Xizmatlar', href: '/admin/services', icon: Briefcase },
  { name: 'Kategoriyalar', href: '/admin/categories', icon: Tags },
  { name: 'Savdolar', href: '/admin/sales', icon: ShoppingCart },
  { name: 'Hisobotlar', icon: BarChart2, submenu: [
      { name: 'Umumiy', href: '/admin/reports' },
      { name: 'Mahsulotlar', href: '/admin/reports/products' }
  ] },
  { name: 'Foydalanuvchilar', href: '/admin/users', icon: Users },
  { name: 'Sozlamalar', href: '/admin/settings', icon: Settings },
];

const logout = async () => {
  await authStore.logout();
  navigateTo('/login');
};
</script>

<template>
  <div class="h-screen bg-gray-100 flex overflow-hidden">
    
    <!-- Mobile Sidebar Overlay -->
    <div 
      v-if="isMobileMenuOpen" 
      @click="isMobileMenuOpen = false" 
      class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden"
    ></div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:shadow-sm flex flex-col shrink-0 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="h-16 flex items-center justify-between px-6 border-b border-gray-100">
        <h1 class="text-xl font-bold text-primary-600">Admin Panel</h1>
        <button @click="isMobileMenuOpen = false" class="lg:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-md">
          <X class="w-5 h-5" />
        </button>
      </div>
      
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <template v-for="item in navigation" :key="item.name">
          <div v-if="authStore.user?.role === 'admin' || item.name === 'Inventarizatsiya' || item.name === 'Kassa (POS)'">
            <NuxtLink 
              v-if="!item.submenu"
              :to="item.href"
              @click="isMobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-colors"
              active-class="bg-primary-50 text-primary-600"
            >
              <component :is="item.icon" class="w-5 h-5" />
              {{ item.name }}
            </NuxtLink>
            
            <div v-else class="space-y-1">
              <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium text-gray-700 cursor-default">
                <component :is="item.icon" class="w-5 h-5" />
                {{ item.name }}
              </div>
              <div class="pl-11 space-y-1 relative before:content-[''] before:absolute before:left-5 before:top-0 before:bottom-4 before:w-px before:bg-gray-200">
                <NuxtLink 
                  v-for="sub in item.submenu"
                  :key="sub.name"
                  :to="sub.href"
                  @click="isMobileMenuOpen = false"
                  class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-primary-600 hover:bg-primary-50 transition-colors relative"
                  exact-active-class="text-primary-600 bg-primary-50 font-semibold"
                >
                  <span class="absolute left-[-1.5rem] top-1/2 w-4 h-px bg-gray-200 -translate-y-1/2"></span>
                  {{ sub.name }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </template>
      </nav>

      <div class="p-4 border-t border-gray-100">
        <button @click="logout" class="flex items-center gap-3 px-3 py-2.5 w-full rounded-lg font-medium text-red-600 hover:bg-red-50 transition-colors">
          <LogOut class="w-5 h-5" />
          Chiqish
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top header -->
      <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 lg:px-8 shrink-0">
        <div class="flex items-center gap-3">
          <button @click="isMobileMenuOpen = true" class="lg:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-md transition-colors">
            <Menu class="w-6 h-6" />
          </button>
          <h2 class="text-lg lg:text-xl font-semibold text-gray-800">
            <!-- Dynamic title could go here based on route -->
          </h2>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-bold">
              A
            </div>
            <span class="font-medium text-gray-700">Admin</span>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <div class="flex-1 overflow-y-auto p-4 lg:p-8">
        <slot />
      </div>
    </main>

    <!-- Global Toast Notification -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="transform translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 sm:translate-x-0"
      leave-to-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    >
      <div v-if="toast" class="fixed bottom-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
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
