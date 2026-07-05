import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useApi } from '~/composables/useApi';

export const useProductStore = defineStore('product', () => {
  const products = ref([]);
  const categories = ref([]);
  const isLoading = ref(false);

  const fetchCategories = async () => {
    const api = useApi();
    try {
      const res = await api('/categories?all=1');
      categories.value = res;
    } catch (e) {
      console.error(e);
    }
  };

  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

  const fetchProducts = async (params = {}) => {
    const api = useApi();
    isLoading.value = true;
    try {
      const queryParams = new URLSearchParams(params).toString();
      const res = await api(`/products?${queryParams}`);
      if (res.data) {
        products.value = res.data;
        pagination.value = {
          current_page: res.current_page,
          last_page: res.last_page,
          total: res.total
        };
      } else {
        products.value = res;
      }
    } catch (e) {
      console.error(e);
    } finally {
      isLoading.value = false;
    }
  };

  const searchByBarcode = async (barcode) => {
    const api = useApi();
    try {
      const res = await api(`/products/barcode/${barcode}`);
      return res;
    } catch (e) {
      return null;
    }
  };

  return { products, categories, isLoading, fetchCategories, fetchProducts, searchByBarcode, pagination };
});
