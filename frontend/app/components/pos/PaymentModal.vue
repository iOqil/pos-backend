<script setup>
import { ref, computed, watch } from 'vue';
import { X, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  total: Number,
  defaultMethod: {
    type: String,
    default: 'cash'
  }
});

const emit = defineEmits(['close', 'confirm']);

const paymentMethod = ref('cash');
const receivedAmount = ref(0);

const isMix = computed(() => paymentMethod.value === 'mix');

const cashAmount = ref(0);
const cardAmount = ref(0);

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    paymentMethod.value = props.defaultMethod;
    receivedAmount.value = props.total;
    cashAmount.value = props.total;
    cardAmount.value = 0;
  }
});

watch(paymentMethod, (method) => {
  if (method === 'cash') {
    receivedAmount.value = props.total;
  } else if (method === 'mix') {
    cashAmount.value = props.total;
    cardAmount.value = 0;
  }
});

const change = computed(() => {
  if (isMix.value) {
    return (cashAmount.value + cardAmount.value) - props.total;
  }
  return receivedAmount.value - props.total;
});

const confirmPayment = () => {
  if (change.value < 0 && !isMix.value && paymentMethod.value === 'cash') return;
  
  emit('confirm', {
    method: paymentMethod.value,
    received: isMix.value ? cashAmount.value + cardAmount.value : receivedAmount.value,
    cashAmount: cashAmount.value,
    cardAmount: cardAmount.value,
    change: change.value
  });
};

</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
        <h2 class="text-xl font-bold text-gray-800">To'lovni amalga oshirish</h2>
        <button @click="$emit('close')" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full transition-colors">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <div class="text-center mb-6">
          <div class="text-sm text-gray-500 mb-1">Jami to'lanadigan summa</div>
          <div class="text-4xl font-extrabold text-primary-600">{{ total.toLocaleString() }} <span class="text-2xl text-gray-600 font-bold">so'm</span></div>
        </div>

        <!-- Payment Method selector -->
        <div class="flex gap-2 p-1 bg-gray-100 rounded-lg mb-6">
          <button 
            @click="paymentMethod = 'cash'"
            :class="['flex-1 py-2 text-sm font-medium rounded-md transition-all', paymentMethod === 'cash' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700']"
          >
            Naqd
          </button>
          <button 
            @click="paymentMethod = 'card'"
            :class="['flex-1 py-2 text-sm font-medium rounded-md transition-all', paymentMethod === 'card' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700']"
          >
            Karta
          </button>
          <button 
            @click="paymentMethod = 'mix'"
            :class="['flex-1 py-2 text-sm font-medium rounded-md transition-all', paymentMethod === 'mix' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700']"
          >
            Aralash
          </button>
        </div>

        <!-- Inputs -->
        <div v-if="paymentMethod === 'cash'" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Mijozdan olingan summa (Naqd)</label>
          <input v-model.number="receivedAmount" type="number" class="w-full text-xl font-bold p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>

        <div v-if="paymentMethod === 'card'" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Kartadan yechilgan summa</label>
          <input :value="total" readonly type="number" class="w-full text-xl font-bold p-3 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg focus:outline-none">
        </div>

        <div v-if="paymentMethod === 'mix'" class="mb-4 space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Naqd berilgan summa</label>
            <input v-model.number="cashAmount" @input="cardAmount = Math.max(0, total - cashAmount)" type="number" class="w-full text-xl font-bold p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kartadan yechilgan summa</label>
            <input v-model.number="cardAmount" type="number" class="w-full text-xl font-bold p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:outline-none">
          </div>
        </div>

        <!-- Change Calculation -->
        <div v-if="(paymentMethod === 'cash' || paymentMethod === 'mix')" class="mt-6 p-4 rounded-xl border flex items-center justify-between" :class="change >= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-800' : 'bg-red-50 border-red-100 text-red-800'">
          <span class="font-medium">Qaytim (sdacha):</span>
          <span class="text-xl font-bold">{{ change >= 0 ? change.toLocaleString() : 0 }} so'm</span>
        </div>

      </div>

      <!-- Footer / Actions -->
      <div class="p-4 border-t border-gray-100 bg-gray-50 flex gap-3">
        <button @click="$emit('close')" class="flex-1 px-4 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
          Bekor qilish
        </button>
        <button 
          @click="confirmPayment"
          :disabled="change < 0 && paymentMethod !== 'card'"
          class="flex-1 px-4 py-3 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <CheckCircle2 class="w-5 h-5" />
          Tasdiqlash
        </button>
      </div>

    </div>
  </div>
</template>
