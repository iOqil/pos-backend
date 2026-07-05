<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Search, Camera, CreditCard, Banknote, ReceiptText, Trash2, Plus, Minus, LayoutGrid, List, X, ShoppingCart } from 'lucide-vue-next';
import { useProductStore } from '~/stores/product';
import { usePosStore } from '~/stores/pos';
import { useSettingsStore } from '~/stores/settings';
import { useApi } from '~/composables/useApi';

const productStore = useProductStore();
const posStore = usePosStore();
const settingsStore = useSettingsStore();

const isCartOpen = ref(false);

const products = computed(() => productStore.products);

const activeCategory = ref(null);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(20);

let searchTimeout = null;

const loadProducts = async () => {
  await productStore.fetchProducts({
    search: searchQuery.value,
    category_id: activeCategory.value || '',
    page: currentPage.value,
    per_page: perPage.value
  });
};

onMounted(async () => {
  await productStore.fetchCategories();
  await loadProducts();
});

watch([searchQuery, activeCategory], () => {
  currentPage.value = 1;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadProducts();
  }, 300);
});

const categories = computed(() => {
  return [{ id: null, name: 'Barcha' }, ...productStore.categories];
});

// Cart logic moved to store
const subtotal = computed(() => posStore.activeSession.cart.reduce((sum, item) => sum + (item.product.price * item.quantity) - (item.discount || 0), 0));
const total = computed(() => Math.max(0, subtotal.value - (posStore.activeSession.discount || 0)));

const showPaymentModal = ref(false);
const selectedPaymentMethod = ref('cash');
const showSuccessModal = ref(false);
const successMessage = ref('');

const openPayment = (method) => {
  if (posStore.activeSession.cart.length === 0) return;
  selectedPaymentMethod.value = method;
  showPaymentModal.value = true;
};

const handlePaymentConfirm = async (paymentData) => {
  const api = useApi();
  
  // Prepare payload for backend
  const payload = {
    customer_id: null,
    payment_method: paymentData.method,
    amount_paid: paymentData.received,
    cash_amount: paymentData.cashAmount,
    card_amount: paymentData.cardAmount,
    items: posStore.activeSession.cart.map(item => ({
      product_id: item.product.id,
      quantity: item.quantity,
      unit_price: item.product.price,
      discount: item.discount || 0
    })),
    discount_amount: posStore.activeSession.discount || 0
  };

  try {
    const res = await api('/sales', {
      method: 'POST',
      body: payload
    });
    
    // Simulate Xprinter command execution
    console.log('Sending ESC/POS command to printer...', res);
    
    successMessage.value = "To'lov qabul qilindi! Chek chop etilmoqda...";
    showSuccessModal.value = true;
    
    // Reset state via store
    posStore.clearActiveCart();
    showPaymentModal.value = false;
    
    // Refresh products to update stock
    await productStore.fetchProducts();
  } catch(e) {
    console.error(e);
    alert("Xatolik yuz berdi: " + (e.data?.message || 'Noma\'lum xato'));
  }
};

</script>

<template>
  <div class="flex-1 flex w-full h-full dark:bg-gray-900 relative overflow-hidden">
    <!-- Left Section: Products & Search -->
    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 pb-20 lg:pb-0 h-full overflow-hidden">
      
      <!-- Search Bar -->
      <div class="p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex gap-4 items-center">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Qidirish yoki Barcode skanerlash..." 
            class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
          >
        </div>
        <button class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-3 rounded-lg flex items-center justify-center transition-colors">
          <Camera class="w-6 h-6" />
        </button>
        <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
          <button @click="settingsStore.posViewMode = 'grid'" :class="['p-2 rounded-md transition-colors', settingsStore.posViewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200']" title="Grid ko'rinishi">
            <LayoutGrid class="w-5 h-5" />
          </button>
          <button @click="settingsStore.posViewMode = 'list'" :class="['p-2 rounded-md transition-colors', settingsStore.posViewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200']" title="Ro'yxat ko'rinishi">
            <List class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Categories -->
      <div class="p-4 flex gap-2 overflow-x-auto no-scrollbar shrink-0">
        <button 
          v-for="cat in categories" 
          :key="cat.id"
          @click="activeCategory = cat.id"
          :class="[
            'px-6 py-2 rounded-full font-medium whitespace-nowrap transition-colors',
            activeCategory === cat.id ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Session Tabs -->
      <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 flex items-center gap-2 overflow-x-auto no-scrollbar shrink-0">
        <div class="flex gap-2">
          <div 
            v-for="session in posStore.sessions" 
            :key="session.id"
            class="group relative flex items-center"
          >
            <button 
              @click="posStore.switchSession(session.id)"
              :class="[
                'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                posStore.activeSessionId === session.id 
                  ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm border-t-2 border-t-primary-500' 
                  : 'bg-transparent text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >
              {{ session.name }}
              <span v-if="session.cart.length > 0" class="ml-1 text-xs bg-primary-100 text-primary-700 px-1.5 rounded-full">{{ session.cart.length }}</span>
            </button>
            <button 
              @click="posStore.closeSession(session.id)"
              class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 bg-red-100 text-red-600 rounded-full w-4 h-4 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all z-10"
              title="Sessiyani yopish"
            >
              <X class="w-3 h-3" />
            </button>
          </div>
        </div>
        <button 
          @click="posStore.createSession()"
          class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 hover:bg-white text-gray-600 hover:text-primary-600 transition-colors shadow-sm ml-2"
          title="Yangi sessiya (Tab) qo'shish"
        >
          <Plus class="w-5 h-5" />
        </button>
      </div>

      <!-- Products Grid/List -->
      <div class="flex-1 p-2 md:p-4 overflow-y-auto flex flex-col">
        <div v-if="productStore.isLoading" class="flex-1 flex items-center justify-center text-gray-400">
          Yuklanmoqda...
        </div>
        <div v-else-if="products.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-2">
          <Package class="w-12 h-12 text-gray-300" />
          Mahsulot topilmadi
        </div>
        
        <div v-else-if="settingsStore.posViewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4 flex-1 content-start">
          <div 
            v-for="product in products" 
            :key="product.id"
            @click="product.stock_quantity > 0 ? posStore.addToCart(product) : null"
            :class="[
              'bg-white dark:bg-gray-800 rounded-xl p-4 border shadow-sm transition-all flex flex-col h-full relative overflow-hidden',
              product.stock_quantity > 0 
                ? 'border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-500 cursor-pointer' 
                : 'border-red-200 dark:border-red-800/50 cursor-not-allowed bg-red-50/30 dark:bg-red-900/10'
            ]"
          >
            <div class="w-full aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg mb-3 flex items-center justify-center text-gray-400 overflow-hidden shrink-0 relative">
              <img v-if="product.image" :src="`http://localhost:8000/storage/${product.image}`" class="w-full h-full object-cover" :alt="product.name" />
              <span v-else class="text-xs">Rasm yo'q</span>

              <div v-if="product.stock_quantity <= 0" class="absolute inset-0 bg-white/50 dark:bg-black/50 backdrop-blur-[2px] flex items-center justify-center z-10">
                <span class="bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded shadow-sm rotate-[-12deg] uppercase tracking-wider border border-red-400">Tugagan</span>
              </div>
            </div>
            <h3 class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2 leading-tight flex-1">{{ product.name }}</h3>
            <div class="mt-2 flex items-end justify-between relative z-20">
              <span class="font-bold text-primary-600 dark:text-primary-400">{{ parseInt(product.price).toLocaleString() }} so'm</span>
              <span :class="['text-xs font-medium', product.stock_quantity <= 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400']">{{ product.stock_quantity }} dona</span>
            </div>
          </div>
        </div>

        <div v-else class="flex flex-col gap-2 flex-1 content-start">
          <div 
            v-for="product in products" 
            :key="'list-'+product.id"
            @click="product.stock_quantity > 0 ? posStore.addToCart(product) : null"
            :class="[
              'rounded-lg p-3 border shadow-sm transition-all flex items-center justify-between relative overflow-hidden',
              product.stock_quantity > 0 
                ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-500 cursor-pointer' 
                : 'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800/50 cursor-not-allowed'
            ]"
          >
            <div v-if="product.stock_quantity <= 0" class="absolute inset-0 bg-white/40 dark:bg-black/40 backdrop-blur-[1px] z-10 flex items-center justify-center">
               <span class="bg-red-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-sm uppercase tracking-wider border border-red-400">Tugagan</span>
            </div>

            <div class="flex items-center gap-4 flex-1 relative z-20">
              <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center text-gray-400 overflow-hidden shrink-0">
                <img v-if="product.image" :src="`http://localhost:8000/storage/${product.image}`" class="w-full h-full object-cover" :alt="product.name" />
                <span v-else class="text-[10px]">Yo'q</span>
              </div>
              <div>
                <h3 class="font-medium text-gray-800 dark:text-gray-200">{{ product.name }}</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ product.barcode || product.sku || 'Kodsiz' }}</span>
              </div>
            </div>
            <div class="flex flex-col items-end relative z-20">
              <span class="font-bold text-primary-600 dark:text-primary-400">{{ parseInt(product.price).toLocaleString() }} so'm</span>
              <span :class="['text-xs', product.stock_quantity <= 0 ? 'text-red-600 font-bold' : 'text-gray-500 dark:text-gray-400']">{{ product.stock_quantity }} dona</span>
            </div>
          </div>
        </div>

        <!-- Pagination Controls -->
        <div v-if="productStore.pagination && productStore.pagination.last_page > 1" class="mt-6 flex justify-center items-center gap-4 shrink-0">
          <button 
            @click="currentPage--; loadProducts()" 
            :disabled="productStore.pagination.current_page === 1"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm bg-white dark:bg-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors font-medium shadow-sm"
          >
            Oldingi
          </button>
          <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
            {{ productStore.pagination.current_page }} / {{ productStore.pagination.last_page }}
          </span>
          <button 
            @click="currentPage++; loadProducts()" 
            :disabled="productStore.pagination.current_page === productStore.pagination.last_page"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-sm bg-white dark:bg-gray-800 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors font-medium shadow-sm"
          >
            Keyingi
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Cart Toggle Button -->
    <div class="lg:hidden absolute bottom-4 left-4 right-4 z-30">
      <button @click="isCartOpen = true" class="w-full bg-primary-600 text-white rounded-2xl shadow-2xl p-4 flex items-center justify-between font-bold hover:bg-primary-700 transition-colors">
        <span class="flex items-center gap-3">
          <ShoppingCart class="w-6 h-6" />
          <span class="text-lg">Savat</span>
          <span class="bg-white text-primary-600 px-2 py-0.5 rounded-full text-sm">{{ posStore.activeSession.cart.length }}</span>
        </span>
        <span class="text-xl">{{ total.toLocaleString() }} so'm</span>
      </button>
    </div>

    <!-- Mobile Cart Overlay -->
    <div 
      v-if="isCartOpen" 
      @click="isCartOpen = false" 
      class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden"
    ></div>

    <!-- Right Section: Cart -->
    <div 
      :class="[
        'flex flex-col bg-white dark:bg-gray-800 shrink-0 shadow-2xl lg:shadow-[-4px_0_15px_rgba(0,0,0,0.02)] z-50 border-l border-gray-200 dark:border-gray-700 transition-transform duration-300',
        'fixed inset-y-0 right-0 w-full sm:w-[400px] lg:static lg:w-[400px] lg:translate-x-0',
        isCartOpen ? 'translate-x-0' : 'translate-x-full'
      ]"
    >
      
      <div class="p-4 bg-primary-50 dark:bg-gray-800 border-b border-primary-100 dark:border-gray-700 flex items-center justify-between">
        <h2 class="font-semibold text-primary-800 dark:text-gray-100 text-lg flex items-center gap-2">
          Savat ({{ posStore.activeSession.name }})
          <span class="bg-primary-600 text-white text-xs py-0.5 px-2 rounded-full">{{ posStore.activeSession.cart.length }}</span>
        </h2>
        <div class="flex items-center gap-2">
          <button @click="posStore.clearActiveCart()" class="text-red-500 hover:text-red-600 dark:hover:text-red-400 p-2 rounded transition-colors" title="Savatni tozalash" v-if="posStore.activeSession.cart.length > 0">
            <Trash2 class="w-5 h-5" />
          </button>
          <button @click="isCartOpen = false" class="lg:hidden p-2 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors">
            <X class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-3">
        <div v-if="posStore.activeSession.cart.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
          <ReceiptText class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-500" />
          <p>Savat bo'sh</p>
        </div>

        <div v-for="(item, index) in posStore.activeSession.cart" :key="index" class="flex flex-col gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-start">
            <h4 class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2 pr-4">{{ item.product.name }}</h4>
            <span class="font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ ((item.product.price * item.quantity) - (item.discount || 0)).toLocaleString() }}</span>
          </div>
          <div class="flex items-center justify-between mt-1">
            <div class="flex flex-col">
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ parseInt(item.product.price).toLocaleString() }} / dona</span>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-[11px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wider">Chegirma:</span>
                <input type="number" :value="item.discount || 0" @input="posStore.setItemDiscount(index, Number($event.target.value))" class="w-20 px-1.5 py-0.5 text-xs border border-gray-200 dark:border-gray-600 rounded text-right focus:outline-none focus:border-primary-400 bg-white dark:bg-gray-800 dark:text-gray-200" />
              </div>
            </div>
            
            <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden shadow-sm">
                <button @click="posStore.updateQuantity(index, -1)" class="px-2 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                  <Minus class="w-4 h-4" />
                </button>
                <input type="number" :value="item.quantity" @input="posStore.setQuantity(index, Number($event.target.value))" class="w-12 text-center text-sm font-medium focus:outline-none dark:bg-gray-800 dark:text-white border-x border-gray-200 dark:border-gray-600 [&::-webkit-inner-spin-button]:appearance-none">
                <button @click="posStore.updateQuantity(index, 1)" class="px-2 py-1 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-gray-700 transition-colors">
                  <Plus class="w-4 h-4" />
                </button>
              </div>
          </div>
        </div>
      </div>

      <!-- Cart Summary -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
        <div class="flex justify-between mb-2 text-gray-600 dark:text-gray-400 items-center">
          <span>Jami summa:</span>
          <span>{{ subtotal.toLocaleString() }} so'm</span>
        </div>
        <div class="flex justify-between mb-4 text-gray-600 dark:text-gray-400 items-center">
          <span>Qo'shimcha chegirma:</span>
          <div class="flex items-center gap-1">
            <span class="text-gray-400">-</span>
            <input type="number" :value="posStore.activeSession.discount || 0" @input="posStore.setDiscount(Number($event.target.value))" class="w-24 p-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-primary-500 text-right font-medium bg-white dark:bg-gray-700 dark:text-white" placeholder="0">
          </div>
        </div>
        <div class="flex justify-between mb-6 text-xl font-bold text-gray-900 dark:text-white">
          <span>To'lash uchun:</span>
          <span class="text-primary-600 dark:text-primary-400">{{ total.toLocaleString() }} so'm</span>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
          <button @click="openPayment('cash')" class="bg-emerald-500 hover:bg-emerald-600 text-white py-3 px-4 rounded-xl font-medium flex items-center justify-center gap-2 transition-colors shadow-sm">
            <Banknote class="w-5 h-5" />
            Naqd
          </button>
          <button @click="openPayment('card')" class="bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded-xl font-medium flex items-center justify-center gap-2 transition-colors shadow-sm">
            <CreditCard class="w-5 h-5" />
            Karta
          </button>
        </div>
        <button @click="openPayment('mix')" class="w-full bg-gray-800 hover:bg-gray-900 text-white py-3 px-4 rounded-xl font-medium transition-colors shadow-sm">
          Aralash to'lov
        </button>
      </div>

    </div>

    <!-- Modals -->
    <PosPaymentModal 
      :show="showPaymentModal" 
      :total="total" 
      :defaultMethod="selectedPaymentMethod"
      @close="showPaymentModal = false"
      @confirm="handlePaymentConfirm"
    />

    <PosSuccessModal 
      :show="showSuccessModal" 
      :message="successMessage" 
      @close="showSuccessModal = false" 
    />
  </div>
</template>

<style scoped>
/* Hide scrollbar for categories but allow scroll */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
