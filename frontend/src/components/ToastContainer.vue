<template>
  <div class="toast-stack" aria-live="polite">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast', `toast--${toast.type}`]"
        @click="remove(toast.id)"
      >
        <span class="toast__icon">
          {{ icons[toast.type] }}
        </span>
        <span class="toast__message">{{ toast.message }}</span>
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
  bottom: 90px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 9999;
  pointer-events: none;
  width: min(340px, 90vw);
}

.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 18px;
  border-radius: var(--radius-md);
  backdrop-filter: blur(20px);
  font-size: 0.9rem;
  cursor: pointer;
  pointer-events: all;
  border: 1px solid transparent;
}

.toast--success {
  background: rgba(16,185,129,0.2);
  border-color: rgba(16,185,129,0.35);
  color: #6ee7b7;
}
.toast--error {
  background: rgba(239,68,68,0.2);
  border-color: rgba(239,68,68,0.35);
  color: #fca5a5;
}
.toast--info {
  background: var(--glass-bg);
  border-color: var(--glass-border);
  color: var(--text-accent);
}

.toast__icon {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.7rem;
  font-weight: 700;
  background: currentColor;
  color: var(--color-bg-deep);
  flex-shrink: 0;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s var(--ease-spring);
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(12px) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}
</style>
