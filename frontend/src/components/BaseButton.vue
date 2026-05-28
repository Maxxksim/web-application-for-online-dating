<template>
  <button
    v-bind="$attrs"
    :disabled="disabled || loading"
    :class="buttonClass"
  >
    <span
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center"
      aria-hidden="true"
    >
      <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="3"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 1 0-7 7v3A10 10 0 0 1 12 2Z"
        />
      </svg>
    </span>

    <span :class="{ 'opacity-0': loading }">
      <slot />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  full: { type: Boolean, default: false },
})

const variantClasses = {
  primary: 'border border-cyan-500 bg-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-400 hover:border-cyan-400',
  secondary: 'border border-slate-200 bg-white text-slate-900 shadow-sm hover:border-cyan-400 hover:bg-cyan-50',
  ghost: 'border border-slate-200 bg-transparent text-cyan-700 hover:border-cyan-400 hover:bg-cyan-50',
  danger: 'border border-rose-500 bg-rose-500 text-white shadow-lg shadow-rose-500/20 hover:bg-rose-600 hover:border-rose-600',
}

const sizeClasses = {
  sm: 'px-4 py-2 text-sm',
  md: 'px-5 py-2.5 text-sm',
  lg: 'px-6 py-3 text-base',
}

const buttonClass = computed(() => [
  'relative inline-flex items-center justify-center gap-2 rounded-full font-semibold tracking-tight transition duration-200 ease-out',
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50',
  'disabled:cursor-not-allowed disabled:opacity-50',
  'active:scale-[0.98]',
  props.full ? 'w-full' : '',
  sizeClasses[props.size] || sizeClasses.md,
  variantClasses[props.variant] || variantClasses.primary,
])
</script>
