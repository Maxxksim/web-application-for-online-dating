<template>
  <div class="action-buttons">
    <button v-if="showUndo" class="action-btn action-btn--undo action-btn--small" @click="handle('undo')" :disabled="disabled" aria-label="Undo">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 7v6h6" />
        <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" />
      </svg>
    </button>

    <button class="action-btn action-btn--no" @click="handle('dislike')" :disabled="disabled" aria-label="Pass">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>

    <button class="action-btn action-btn--yes" @click="handle('like')" :disabled="disabled" aria-label="Like">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
      </svg>
    </button>
  </div>
</template>

<script setup>
defineProps({
  disabled: { type: Boolean, default: false },
  showUndo: { type: Boolean, default: false },
})

const emit = defineEmits(['like', 'dislike', 'undo', 'action'])

const handle = (type) => {
  emit('action', type)
  if (type !== 'action') emit(type)
}
</script>

<style scoped>
.action-buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 14px;
  user-select: none;
  position: relative;
  z-index: 10;
  max-width: 300px;
  width: 100%;
}

.action-btn {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-color);
  background: var(--color-surface);
  box-shadow: var(--shadow-md);
  cursor: pointer;
  transition:
    transform var(--duration-fast) var(--ease-spring),
    box-shadow var(--duration-fast) var(--ease-spring),
    border-color var(--duration-fast) var(--ease-smooth);
}

.action-btn--small {
  width: 44px;
  height: 44px;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-2px) scale(1.06);
  box-shadow: var(--shadow-lg);
}

.action-btn:active:not(:disabled) {
  transform: scale(0.96);
}

.action-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.action-btn--no {
  color: var(--color-rose);
  border-color: rgba(244, 63, 94, 0.25);
}
.action-btn--no:hover:not(:disabled) {
  border-color: var(--color-rose);
}

.action-btn--yes {
  color: var(--color-emerald);
  border-color: rgba(16, 185, 129, 0.3);
}
.action-btn--yes:hover:not(:disabled) {
  border-color: var(--color-emerald);
}

.action-btn--undo {
  color: #eab308; /* yellow-500 */
  border-color: rgba(234, 179, 8, 0.3);
}
.action-btn--undo:hover:not(:disabled) {
  border-color: #eab308;
}
</style>
