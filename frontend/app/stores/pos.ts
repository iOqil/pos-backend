import { defineStore } from 'pinia';
import { ref, watch, computed } from 'vue';

export const usePosStore = defineStore('pos', () => {
  // Session structure: { id: number, name: string, cart: Array, discount: number }
  const defaultSession = () => ({
    id: Date.now(),
    name: 'Sessiya 1',
    cart: [],
    discount: 0
  });

  const sessions = ref([defaultSession()]);
  const activeSessionId = ref(sessions.value[0].id);
  const isLocked = ref(false);

  // Load from localStorage on client side
  if (import.meta.client) {
    const saved = localStorage.getItem('pos_sessions');
    const savedActive = localStorage.getItem('pos_active_session_id');
    
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) {
          sessions.value = parsed;
        }
      } catch (e) {
        console.error('Failed to parse POS sessions', e);
      }
    }
    
    if (savedActive) {
      const activeId = parseInt(savedActive, 10);
      if (sessions.value.some(s => s.id === activeId)) {
        activeSessionId.value = activeId;
      } else {
        activeSessionId.value = sessions.value[0].id;
      }
    }

    const savedLock = localStorage.getItem('pos_is_locked');
    if (savedLock === 'true') {
      isLocked.value = true;
    }
  }

  // Persist changes to localStorage
  watch(sessions, (newSessions) => {
    if (import.meta.client) {
      localStorage.setItem('pos_sessions', JSON.stringify(newSessions));
    }
  }, { deep: true });

  watch(activeSessionId, (newId) => {
    if (import.meta.client) {
      localStorage.setItem('pos_active_session_id', newId.toString());
    }
  });

  watch(isLocked, (locked) => {
    if (import.meta.client) {
      localStorage.setItem('pos_is_locked', locked ? 'true' : 'false');
    }
  });

  const activeSession = computed(() => {
    return sessions.value.find(s => s.id === activeSessionId.value) || sessions.value[0];
  });

  const createSession = () => {
    const newId = Date.now();
    const newNum = sessions.value.length + 1;
    sessions.value.push({
      id: newId,
      name: `Sessiya ${newNum}`,
      cart: [],
      discount: 0
    });
    activeSessionId.value = newId;
  };

  const closeSession = (id) => {
    if (sessions.value.length === 1) {
      // Don't close the last session, just clear it
      sessions.value[0] = defaultSession();
      activeSessionId.value = sessions.value[0].id;
      return;
    }

    const index = sessions.value.findIndex(s => s.id === id);
    if (index > -1) {
      sessions.value.splice(index, 1);
      // Switch active session if needed
      if (activeSessionId.value === id) {
        activeSessionId.value = sessions.value[Math.max(0, index - 1)].id;
      }
    }
  };

  const switchSession = (id) => {
    if (sessions.value.some(s => s.id === id)) {
      activeSessionId.value = id;
    }
  };

  const clearActiveCart = () => {
    const session = activeSession.value;
    if (session) {
      session.cart = [];
      session.discount = 0;
    }
  };

  const addToCart = (product) => {
    const session = activeSession.value;
    const existing = session.cart.find(item => item.product.id === product.id);
    if (existing) {
      if (existing.quantity < product.stock_quantity) {
        existing.quantity++;
      } else {
        alert("Omborda bundan ortiq mahsulot yo'q!");
      }
    } else {
      if (product.stock_quantity > 0) {
        session.cart.push({ product, quantity: 1, discount: 0 });
      } else {
        alert("Mahsulot omborda qolmagan!");
      }
    }
  };

  const removeFromCart = (index) => {
    const session = activeSession.value;
    session.cart.splice(index, 1);
  };

  const updateQuantity = (index, delta) => {
    const session = activeSession.value;
    const item = session.cart[index];
    if (item) {
      const newQuantity = item.quantity + delta;
      if (delta > 0 && newQuantity > item.product.stock_quantity) {
        alert("Omborda bundan ortiq mahsulot yo'q!");
        return;
      }
      item.quantity = newQuantity;
      if (item.quantity <= 0) {
        removeFromCart(index);
      }
    }
  };

  const setQuantity = (index, quantity) => {
    const session = activeSession.value;
    const item = session.cart[index];
    if (item) {
      if (quantity > item.product.stock_quantity) {
        alert("Omborda bundan ortiq mahsulot yo'q!");
        item.quantity = item.product.stock_quantity;
      } else if (quantity <= 0) {
        removeFromCart(index);
      } else {
        item.quantity = quantity;
      }
    }
  };

  const setItemDiscount = (index, amount) => {
    const session = activeSession.value;
    const item = session.cart[index];
    if (item) {
      item.discount = amount;
    }
  };

  const setDiscount = (amount) => {
    const session = activeSession.value;
    if (session) {
      session.discount = amount;
    }
  };

  const lockPos = () => {
    isLocked.value = true;
  };

  const unlockPos = () => {
    isLocked.value = false;
  };

  return {
    sessions,
    activeSessionId,
    activeSession,
    createSession,
    closeSession,
    switchSession,
    clearActiveCart,
    addToCart,
    removeFromCart,
    updateQuantity,
    setQuantity,
    setItemDiscount,
    setDiscount,
    isLocked,
    lockPos,
    unlockPos
  };
});
