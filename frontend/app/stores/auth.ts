import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useApi } from '~/composables/useApi';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const isAuthenticated = ref(false);

  const login = async (credentials) => {
    const api = useApi();
    try {
      const response = await api('/auth/login', {
        method: 'POST',
        body: credentials
      });
      localStorage.setItem('auth_token', response.access_token);
      user.value = response.user;
      isAuthenticated.value = true;
      return true;
    } catch (error) {
      console.error('Login failed', error);
      return false;
    }
  };

  const fetchUser = async () => {
    if (!localStorage.getItem('auth_token')) return;
    
    const api = useApi();
    try {
      const response = await api('/auth/me');
      user.value = response;
      isAuthenticated.value = true;
    } catch (error) {
      localStorage.removeItem('auth_token');
      user.value = null;
      isAuthenticated.value = false;
    }
  };

  const logout = async () => {
    const api = useApi();
    try {
      await api('/auth/logout', { method: 'POST' });
    } catch (e) {}
    localStorage.removeItem('auth_token');
    user.value = null;
    isAuthenticated.value = false;
  };

  return { user, isAuthenticated, login, fetchUser, logout };
});
