<template>
  <div class="action-buttons flex justify-center items-center gap-3 sm:gap-4 mt-0 select-none relative z-10 w-full max-w-[320px] sm:max-w-[360px] px-2 sm:px-4">
    <button class="action-button action-button--no" @click="handle('dislike')" :disabled="disabled" aria-label="Pass">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <button class="action-button action-button--yes" @click="handle('like')" :disabled="disabled" aria-label="Like">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
      </svg>
    </button>
  </div>
</template>

<script setup>
defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['like', 'dislike', 'action'])

const handle = (type) => {
  emit('action', type)
  emit(type)
}
</script>

<style scoped>
.action-button {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(167, 139, 250, 0.25);
  background: rgba(16, 8, 30, 0.85);
  color: #fff;
  box-shadow: 0 10px 20px rgba(10, 6, 20, 0.5);
  transition: transform var(--duration-fast) var(--ease-spring),
    box-shadow var(--duration-fast) var(--ease-spring),
    background var(--duration-fast) var(--ease-smooth),
    border-color var(--duration-fast) var(--ease-smooth);
}

.action-button:hover:not(:disabled) {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 14px 26px rgba(10, 6, 20, 0.6);
}

.action-button:active:not(:disabled) {
  transform: scale(0.97);
}

.action-button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.action-button--no {
  color: #fda4af;
  border-color: rgba(244, 114, 182, 0.4);
  background: rgba(76, 12, 30, 0.6);
}

.action-button--yes {
  color: #6ee7b7;
  border-color: rgba(16, 185, 129, 0.5);
  background: rgba(8, 45, 40, 0.6);
}

@media (max-width: 640px) {
  .action-buttons {
    max-width: 300px;
  }

  .action-button {
    width: 46px;
    height: 46px;
  }
}
</style>
