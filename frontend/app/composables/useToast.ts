import { ref } from 'vue';

const toastState = ref(null);

export const useToast = () => {
  const show = (message, type = 'success') => {
    toastState.value = { message, type, id: Date.now() };
    setTimeout(() => {
      if (toastState.value?.id === toastState.value?.id) {
        toastState.value = null;
      }
    }, 3000);
  };

  const clear = () => {
    toastState.value = null;
  };

  return { toast: toastState, show, clear };
};
