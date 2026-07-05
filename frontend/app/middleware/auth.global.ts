import { defineNuxtRouteMiddleware, navigateTo } from '#app';
import { useAuthStore } from '~/stores/auth';

export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore();
  
  // Try to load user from token on first load if not loaded
  if (import.meta.client && !authStore.isAuthenticated) {
    await authStore.fetchUser();
  }

  const isAuthRequired = to.path.startsWith('/admin') || to.path.startsWith('/pos');

  if (isAuthRequired && !authStore.isAuthenticated) {
    return navigateTo('/login');
  }

  // RBAC for cashiers
  if (authStore.isAuthenticated && authStore.user?.role === 'cashier') {
    const isRestrictedAdminRoute = to.path.startsWith('/admin') && !to.path.startsWith('/admin/inventory');
    if (isRestrictedAdminRoute) {
      return navigateTo('/pos'); // Redirect cashiers to POS
    }
  }

  // Prevent logged in users from visiting login
  if (to.path === '/login' && authStore.isAuthenticated) {
    return navigateTo('/admin');
  }
});
