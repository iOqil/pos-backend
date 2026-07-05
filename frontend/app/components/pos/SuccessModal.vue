<script setup>
import { CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  message: {
    type: String,
    default: "Muvaffaqiyatli bajarildi!"
  }
});

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col items-center p-6 text-center transform scale-100 transition-all">
      <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
        <CheckCircle2 class="w-8 h-8 text-emerald-600" />
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">Muvaffaqiyatli!</h3>
      <p class="text-gray-500 mb-6">{{ message }}</p>
      
      <button @click="$emit('close')" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors">
        Tushunarli
      </button>
    </div>
  </div>
</template>
