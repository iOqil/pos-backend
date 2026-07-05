import { useRuntimeConfig } from '#app';

export const useApi = () => {
  const config = useRuntimeConfig();

  return $fetch.create({
    baseURL: config.public.apiBase,
    onRequest({ request, options }) {
      const token = localStorage.getItem('auth_token');
      if (token) {
        options.headers = options.headers || {};
        options.headers.Authorization = `Bearer ${token}`;
      }
      options.headers = {
        ...options.headers,
        Accept: 'application/json'
      };
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        localStorage.removeItem('auth_token');
        // navigateTo('/login');
      }
    }
  });
};
