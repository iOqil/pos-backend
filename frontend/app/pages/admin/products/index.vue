<script setup>
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { useProductStore } from '~/stores/product';
import { useApi } from '~/composables/useApi';
import { Plus, Edit2, Trash2, X, CheckCircle2, Printer } from 'lucide-vue-next';
import JsBarcode from 'jsbarcode';

definePageMeta({ layout: 'admin' });

const productStore = useProductStore();
const api = useApi();
const searchQuery = ref('');
const selectedCategory = ref('');
const perPage = ref(15);
const currentPage = ref(1);

let searchTimeout = null;

const loadProducts = async () => {
  await productStore.fetchProducts({
    search: searchQuery.value,
    category_id: selectedCategory.value,
    per_page: perPage.value,
    page: currentPage.value
  });
};

onMounted(async () => {
  await productStore.fetchCategories();
  await loadProducts();
});

watch([searchQuery, selectedCategory, perPage], () => {
  currentPage.value = 1;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadProducts();
  }, 300);
});

// Modal State
const showModal = ref(false);
const isEditing = ref(false);
const selectedImage = ref(null);
const imagePreview = ref(null);

const form = ref({
  id: null,
  name: '',
  category_id: '',
  price: 0,
  cost_price: 0,
  stock_quantity: 0,
  barcode: '',
  sku: ''
});
const printSettings = ref({
  width: 40,
  height: 30,
  padding: 2,
  copies: 1,
  showName: true,
  nameFontSize: 11,
  nameMarginBottom: 2,
  showPrice: true,
  priceFontSize: 13,
  priceMarginTop: 2,
  barcodeHeight: 40,
  barcodeWidth: 1.5,
  barcodeFontSize: 12
});

const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    selectedImage.value = file;
    imagePreview.value = URL.createObjectURL(file);
  }
};

const openCreate = () => {
  form.value = {
    id: null, name: '', category_id: '', price: 0, cost_price: 0, stock_quantity: 0, barcode: '', sku: ''
  };
  isEditing.value = false;
  selectedImage.value = null;
  imagePreview.value = null;
  showModal.value = true;
};

const openEdit = (product) => {
  form.value = { ...product };
  isEditing.value = true;
  selectedImage.value = null;
  imagePreview.value = product.image ? `/storage/${product.image}` : null;
  showModal.value = true;
};

const saveProduct = async () => {
  try {
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== '') {
        formData.append(key, form.value[key]);
      }
    });

    if (selectedImage.value) {
      formData.append('image', selectedImage.value);
    }

    if (isEditing.value) {
      // For PUT requests with FormData in Laravel, we use POST method and _method=PUT
      formData.append('_method', 'PUT');
      await api(`/products/${form.value.id}`, { method: 'POST', body: formData });
    } else {
      await api('/products', { method: 'POST', body: formData });
    }
    showModal.value = false;
    await productStore.fetchProducts();
  } catch(e) {
    alert("Xatolik: " + (e.data?.message || 'Noma\'lum xato'));
  }
};

const deleteProduct = async (id) => {
  if (!confirm("O'chirishni tasdiqlaysizmi?")) return;
  try {
    await api(`/products/${id}`, { method: 'DELETE' });
    await productStore.fetchProducts();
  } catch(e) {
    alert("O'chirishda xatolik yuz berdi.");
  }
};

watch(() => [
  form.value.barcode, 
  printSettings.value.barcodeHeight, 
  printSettings.value.barcodeWidth, 
  printSettings.value.barcodeFontSize
], async ([newVal]) => {
  if (newVal) {
    await nextTick();
    try {
      JsBarcode('#barcode-svg', newVal, {
        format: 'CODE128',
        width: printSettings.value.barcodeWidth || 1.5,
        height: printSettings.value.barcodeHeight || 40,
        fontSize: printSettings.value.barcodeFontSize || 12,
        displayValue: true,
        background: 'transparent',
        margin: 0
      });
    } catch (e) {
      console.log('Invalid barcode for generation');
    }
  }
});

const printBarcode = () => {
  const svg = document.getElementById('barcode-svg');
  if (!svg) return;
  const printWindow = window.open('', '_blank');
  
  const p = printSettings.value;
  const name = form.value.name || 'Mahsulot';
  const price = form.value.price ? form.value.price.toLocaleString() + " so'm" : '';

  let stickers = '';
  for(let i=0; i<p.copies; i++) {
    stickers += `
      <div class="sticker">
        ${p.showName ? `<div class="name">${name}</div>` : ''}
        <div class="barcode-container">
          ${svg.outerHTML}
        </div>
        ${p.showPrice ? `<div class="price">${price}</div>` : ''}
      </div>
    `;
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Barcode</title>
        <style>
          @page { size: ${p.width}mm ${p.height}mm; margin: 0; }
          body { 
            margin: 0; 
            padding: 0; 
            background: white; 
            font-family: Arial, sans-serif;
          }
          .sticker {
            width: ${p.width}mm;
            height: ${p.height}mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-sizing: border-box;
            padding: ${p.padding}mm;
            overflow: hidden;
            page-break-after: always;
          }
          .name {
            font-size: ${p.nameFontSize}px;
            font-weight: bold;
            margin-bottom: ${p.nameMarginBottom}px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
          }
          .barcode-container {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
          }
          .barcode-container svg {
            max-width: 100%;
            height: 100%;
          }
          .price {
            font-size: ${p.priceFontSize}px;
            font-weight: bold;
            margin-top: ${p.priceMarginTop}px;
          }
        </style>
      </head>
      <body>
        ${stickers}
        <script>
          window.onload = () => { setTimeout(() => { window.print(); window.close(); }, 300); }
        </scr`+`ipt>
      </body>
    </html>
  `);
  printWindow.document.close();
};
</script>

<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-900">Mahsulotlar</h1>
      <button @click="openCreate" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors">
        <Plus class="w-5 h-5" /> Yangi qo'shish
      </button>
    </div>

    <!-- Toolbar -->
    <div class="bg-white p-4 rounded-t-xl border border-gray-200 border-b-0 flex flex-col md:flex-row md:flex-wrap gap-4 items-start md:items-center justify-between">
      <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto flex-1 max-w-2xl">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Nomi, Barcode yoki SKU qidirish..." 
          class="w-full sm:flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500"
        >
        <select v-model="selectedCategory" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 bg-white">
          <option value="">Barcha kategoriyalar</option>
          <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
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

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-b-xl overflow-hidden flex flex-col">
      <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-medium">
          <tr>
            <th class="px-6 py-4">Nomi</th>
            <th class="px-6 py-4">Kategoriya</th>
            <th class="px-6 py-4">Barcode / SKU</th>
            <th class="px-6 py-4 text-right">Narxi</th>
            <th class="px-6 py-4 text-center">Zaxira</th>
            <th class="px-6 py-4 text-center">Amallar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="productStore.isLoading">
            <td colspan="6" class="px-6 py-8 text-center text-gray-400">Yuklanmoqda...</td>
          </tr>
          <tr v-for="product in productStore.products" :key="product.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-900">{{ product.name }}</td>
            <td class="px-6 py-4">{{ product.category?.name || 'Kategoriyasiz' }}</td>
            <td class="px-6 py-4 text-xs text-gray-500">{{ product.barcode || '-' }} <br> {{ product.sku || '-' }}</td>
            <td class="px-6 py-4 text-right font-semibold">{{ parseInt(product.price).toLocaleString() }} so'm</td>
            <td class="px-6 py-4 text-center">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', product.stock_quantity > 10 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800']">
                {{ product.stock_quantity }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openEdit(product)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"><Edit2 class="w-4 h-4" /></button>
                <button @click="deleteProduct(product.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"><Trash2 class="w-4 h-4" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!productStore.isLoading && productStore.products.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Hech qanday mahsulot topilmadi.</td>
          </tr>
        </tbody>
      </table>
      </div>
      
      <!-- Pagination Controls -->
      <div v-if="productStore.pagination && productStore.pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
        <span class="text-sm text-gray-600">Sahifa {{ productStore.pagination.current_page }} / {{ productStore.pagination.last_page }} (Jami: {{ productStore.pagination.total }})</span>
        <div class="flex gap-2">
          <button 
            @click="currentPage--; loadProducts()" 
            :disabled="productStore.pagination.current_page === 1"
            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Oldingi
          </button>
          <button 
            @click="currentPage++; loadProducts()" 
            :disabled="productStore.pagination.current_page === productStore.pagination.last_page"
            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            Keyingi
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col my-auto max-h-[90vh]">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50 sticky top-0">
          <h2 class="text-xl font-bold text-gray-800">{{ isEditing ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot' }}</h2>
          <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="saveProduct" class="p-6 flex flex-col gap-4 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="md:col-span-2 flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 mb-2 relative overflow-hidden">
              <img v-if="imagePreview" :src="imagePreview" class="h-32 w-auto object-cover rounded shadow-sm mb-2 z-10" />
              <div v-else class="text-gray-400 mb-2">
                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="mt-1 text-sm">Rasm yuklash</p>
              </div>
              <input type="file" accept="image/*" @change="handleImageChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Nomi</label>
              <input v-model="form.name" required type="text" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kategoriya</label>
              <select v-model="form.category_id" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <option disabled value="">Kategoriyani tanlang</option>
                <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Zaxiradagi soni</label>
              <input v-model.number="form.stock_quantity" required type="number" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sotuv narxi (so'm)</label>
              <input v-model.number="form.price" required type="number" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tan narxi (so'm)</label>
              <input v-model.number="form.cost_price" type="number" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
              <div class="flex gap-2">
                <input v-model="form.barcode" type="text" placeholder="Skanerlang yoki yarating..." class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <button type="button" @click="form.barcode = '2000' + Math.floor(100000000 + Math.random() * 900000000)" class="px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-lg text-sm font-medium transition-colors whitespace-nowrap" title="Avtomat generatsiya">
                  Yaratish
                </button>
              </div>
              <div v-if="form.barcode" class="mt-4 p-5 bg-gray-50 border border-gray-200 rounded-2xl grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                
                <!-- Live Preview -->
                <div class="flex flex-col items-center w-full sticky top-4">
                  <h4 class="text-sm font-semibold text-gray-700 mb-3 w-full text-center bg-gray-200 py-1 rounded">Jonli Ko'rinish</h4>
                  <div class="bg-white shadow-md border border-gray-300 flex flex-col justify-center items-center text-center overflow-hidden transition-all duration-300 relative"
                       :style="{ 
                         width: printSettings.width + 'mm', 
                         height: printSettings.height + 'mm', 
                         padding: printSettings.padding + 'mm' 
                       }">
                    <div v-if="printSettings.showName" class="font-bold leading-tight w-full whitespace-nowrap overflow-hidden text-ellipsis transition-all"
                         :style="{ fontSize: printSettings.nameFontSize + 'px', marginBottom: printSettings.nameMarginBottom + 'px' }">
                      {{ form.name || 'Mahsulot nomi' }}
                    </div>
                    <div class="flex-1 flex items-center justify-center w-full min-h-0 overflow-hidden">
                      <svg id="barcode-svg" class="max-w-full max-h-full"></svg>
                    </div>
                    <div v-if="printSettings.showPrice" class="font-bold leading-none w-full transition-all"
                         :style="{ fontSize: printSettings.priceFontSize + 'px', marginTop: printSettings.priceMarginTop + 'px' }">
                      {{ form.price ? form.price.toLocaleString() + " so'm" : "0 so'm" }}
                    </div>
                    <!-- Ruler overlay hint -->
                    <div class="absolute inset-0 border border-dashed border-primary-300/30 pointer-events-none" :style="{ margin: printSettings.padding + 'mm'}"></div>
                  </div>
                  <p class="text-xs text-gray-400 mt-3 text-center">Haqiqiy hajm ({{ printSettings.width }}x{{ printSettings.height }}mm)</p>
                </div>

                <!-- Settings -->
                <div class="flex flex-col w-full">
                  <div class="space-y-4 max-h-[350px] overflow-y-auto pr-3" style="scrollbar-width: thin;">
                    
                    <div class="grid grid-cols-2 gap-3">
                      <div>
                        <label class="block text-xs text-gray-500 mb-1 font-medium">Qog'oz Eni (mm)</label>
                        <input v-model.number="printSettings.width" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                      </div>
                      <div>
                        <label class="block text-xs text-gray-500 mb-1 font-medium">Qog'oz Bo'yi (mm)</label>
                        <input v-model.number="printSettings.height" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                      </div>
                      <div>
                        <label class="block text-xs text-gray-500 mb-1 font-medium">Chekka (Padding mm)</label>
                        <input v-model.number="printSettings.padding" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                      </div>
                      <div>
                        <label class="block text-xs text-gray-500 mb-1 font-medium">Nusxalar soni</label>
                        <input v-model.number="printSettings.copies" type="number" min="1" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                      </div>
                    </div>

                    <div class="border-t border-gray-200 pt-3">
                      <div class="flex items-center justify-between mb-3">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 cursor-pointer">
                          <input type="checkbox" v-model="printSettings.showName" class="rounded text-primary-600 focus:ring-primary-500 w-4 h-4"> Nomi ko'rinsin
                        </label>
                      </div>
                      <div v-if="printSettings.showName" class="grid grid-cols-2 gap-3">
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Harf (px)</label>
                          <input v-model.number="printSettings.nameFontSize" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Pastki oraliq (px)</label>
                          <input v-model.number="printSettings.nameMarginBottom" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                      </div>
                    </div>

                    <div class="border-t border-gray-200 pt-3">
                      <h5 class="text-sm font-semibold text-gray-800 mb-3">Shtrix-kod o'lchamlari</h5>
                      <div class="grid grid-cols-3 gap-2">
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Bo'yi</label>
                          <input v-model.number="printSettings.barcodeHeight" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Qalinligi</label>
                          <input v-model.number="printSettings.barcodeWidth" type="number" step="0.1" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Raqam (px)</label>
                          <input v-model.number="printSettings.barcodeFontSize" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                      </div>
                    </div>

                    <div class="border-t border-gray-200 pt-3">
                      <div class="flex items-center justify-between mb-3">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 cursor-pointer">
                          <input type="checkbox" v-model="printSettings.showPrice" class="rounded text-primary-600 focus:ring-primary-500 w-4 h-4"> Narxi ko'rinsin
                        </label>
                      </div>
                      <div v-if="printSettings.showPrice" class="grid grid-cols-2 gap-3">
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Harf (px)</label>
                          <input v-model.number="printSettings.priceFontSize" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                        <div>
                          <label class="block text-xs text-gray-500 mb-1">Yuqori oraliq (px)</label>
                          <input v-model.number="printSettings.priceMarginTop" type="number" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:outline-none">
                        </div>
                      </div>
                    </div>

                  </div>
                  
                  <button type="button" @click="printBarcode" class="mt-4 w-full py-3.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2 shadow-lg shadow-primary-500/30">
                    <Printer class="w-5 h-5" /> Chop etish (Printer)
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SKU (ichki kod)</label>
              <div class="flex gap-2">
                <input v-model="form.sku" required type="text" placeholder="Masalan: ITM-001" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <button type="button" @click="form.sku = 'SKU-' + Math.random().toString(36).substr(2, 6).toUpperCase()" class="px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 rounded-lg text-sm font-medium transition-colors" title="Avtomat generatsiya">
                  Yaratish
                </button>
              </div>
            </div>
          </div>
          
          <div class="flex gap-3 mt-4 pt-4 border-t border-gray-100">
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
