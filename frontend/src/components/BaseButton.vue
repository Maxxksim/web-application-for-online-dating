<template>
  <button
    :class="['btn', `btn--${variant}`, `btn--${size}`, { 'btn--loading': loading, 'btn--full': full }]"
    :disabled="disabled || loading"
    v-bind="$attrs"
  >
    <span v-if="loading" class="btn__spinner" aria-hidden="true" />
    <slot />
  </button>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger
  size:    { type: String, default: 'md' },       // sm | md | lg
  loading: { type: Boolean, default: false },
  disabled:{ type: Boolean, default: false },
  full:    { type: Boolean, default: false },
})
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: var(--font-body);
  font-weight: 500;
  letter-spacing: 0.02em;
  border-radius: var(--radius-full);
  border: none;
  cursor: pointer;
  transition:
    transform var(--duration-fast) var(--ease-spring),
    box-shadow var(--duration-normal) var(--ease-smooth),
    opacity var(--duration-fast) var(--ease-smooth);
  position: relative;
  overflow: hidden;
  white-space: nowrap;
  user-select: none;
}

.btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.06);
  opacity: 0;
  transition: opacity var(--duration-fast);
}
.btn:hover:not(:disabled)::after { opacity: 1; }
.btn:active:not(:disabled) { transform: scale(0.97); }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }

/* Sizes */
.btn--sm { padding: 8px 20px; font-size: 0.8rem; }
.btn--md { padding: 12px 28px; font-size: 0.9rem; }
.btn--lg { padding: 16px 40px; font-size: 1rem; }

/* Full width */
.btn--full { width: 100%; }

/* Variants */
.btn--primary {
  background: var(--gradient-primary);
  color: #fff;
  box-shadow: 0 4px 20px rgba(139,92,246,0.4);
}
.btn--primary:hover:not(:disabled) {
  box-shadow: 0 6px 30px rgba(139,92,246,0.6);
}

.btn--secondary {
  background: var(--color-bg-elevated);
  color: var(--text-primary);
  border: 1px solid var(--glass-border);
}
.btn--secondary:hover:not(:disabled) {
  border-color: var(--border-active);
}

.btn--ghost {
  background: transparent;
  color: var(--color-violet-400);
  border: 1px solid var(--glass-border);
}
.btn--ghost:hover:not(:disabled) {
  background: rgba(139,92,246,0.08);
  border-color: var(--border-active);
}

.btn--danger {
  background: linear-gradient(135deg, #dc2626, #f87171);
  color: #fff;
  box-shadow: 0 4px 20px rgba(220,38,38,0.3);
}

/* Spinner */
.btn--loading { color: transparent; }

.btn__spinner {
  position: absolute;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
