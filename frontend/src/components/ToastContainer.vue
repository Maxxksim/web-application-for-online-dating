<template>
  <div class="toast-stack" aria-live="polite">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="['toast', `toast--${t.type}`]"
        @click="remove(t.id)"
      >
        <span class="toast__icon">{{ icons[t.type] }}</span>
        <span class="toast__message">{{ t.message }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '@/composables/useToast.js'

const { toasts, remove } = useToast()

const icons = { success: '✓', error: '✕', info: 'i' }
</script>

<style scoped>
.toast-stack {
  position: fixed;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 9999;
  pointer-events: none;
  width: min(340px, 90vw);
}

.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  backdrop-filter: blur(12px);
  font-size: 0.88rem;
  cursor: pointer;
  pointer-events: all;
  border: 1px solid transparent;
  box-shadow: var(--shadow-md);
}

.toast--success {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.25);
  color: #059669;
}
.toast--error {
  background: rgba(244, 63, 94, 0.1);
  border-color: rgba(244, 63, 94, 0.25);
  color: #e11d48;
}
.toast--info {
  background: rgba(14, 165, 233, 0.1);
  border-color: rgba(14, 165, 233, 0.25);
  color: var(--color-accent);
}

.toast__icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.65rem;
  font-weight: 700;
  background: currentColor;
  color: #fff;
  flex-shrink: 0;
}

.toast__message {
  color: inherit;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s var(--ease-spring);
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(10px) scale(0.96);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}
</style>
