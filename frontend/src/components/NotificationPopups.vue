<template>
  <div class="notification-popups" aria-live="polite">
    <TransitionGroup name="popup">
      <div
        v-for="p in popups"
        :key="p.id"
        :class="['popup', `popup--${p.type}`]"
        @click="removePopup(p.id)"
      >
        <div class="popup__icon">
          <span v-if="p.type === 'like'">❤️</span>
          <span v-else-if="p.type === 'match'">✨</span>
          <span v-else-if="p.type === 'message'">💬</span>
          <span v-else>🔔</span>
        </div>
        <div class="popup__content">
          <p class="popup__label">{{ p.label }}</p>
          <p class="popup__message">{{ p.message }}</p>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationsStore } from '@/stores/notifications.js'

const store = useNotificationsStore()
const popups = computed(() => store.popups)
const removePopup = (id) => store.removePopup(id)
</script>

<style scoped>
.notification-popups {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 9999;
  pointer-events: none;
  width: min(300px, calc(100vw - 40px));
}

.popup {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
  cursor: pointer;
  pointer-events: auto;
}

.popup--like { border-left: 4px solid #f43f5e; }
.popup--match { border-left: 4px solid #10b981; }
.popup--message { border-left: 4px solid var(--color-accent); }

.popup__icon {
  font-size: 1.2rem;
}

.popup__content {
  flex: 1;
}

.popup__label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  margin: 0 0 2px;
}

.popup__message {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

/* Transitions */
.popup-enter-active,
.popup-leave-active {
  transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.popup-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.95);
}

.popup-leave-to {
  opacity: 0;
  transform: translateX(30px) scale(0.95);
}
</style>
