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
      <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4 animate-[spin_0.8s_linear_infinite]">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
        <path class="opacity-75" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 1 0-7 7v3A10 10 0 0 1 12 2Z" />
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
  variant:  { type: String, default: 'primary' },
  size:     { type: String, default: 'md' },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  full:     { type: Boolean, default: false },
})

const variantClasses = {
  primary:   'bg-[linear-gradient(135deg,#f0896c,#e06b8f)] text-white shadow-[0_4px_14px_rgba(224,107,143,0.25)] hover:not:disabled:-translate-y-1 hover:not:disabled:shadow-[0_10px_26px_rgba(224,107,143,0.45)] hover:not:disabled:brightness-105 hover:not:disabled:saturate-125',
  secondary: 'bg-white/75 border-slate-300 text-slate-900 backdrop-blur-md hover:not:disabled:-translate-y-0.5 hover:not:disabled:shadow-[0_10px_20px_rgba(15,23,42,0.12)] hover:not:disabled:border-sky-400 hover:not:disabled:bg-white hover:not:disabled:ring-2 hover:not:disabled:ring-cyan-200/60',
  ghost:     'bg-transparent border-slate-200 text-teal-500 hover:not:disabled:-translate-y-0.5 hover:not:disabled:bg-teal-500/15 hover:not:disabled:border-teal-500 hover:not:disabled:text-teal-600 hover:not:disabled:shadow-[0_8px_18px_rgba(20,184,166,0.25)]',
  outline:   'bg-white/80 border-cyan-300 text-cyan-700 shadow-[0_4px_14px_rgba(14,165,233,0.15)] hover:not:disabled:-translate-y-0.5 hover:not:disabled:bg-cyan-50 hover:not:disabled:border-cyan-400 hover:not:disabled:text-cyan-800 hover:not:disabled:shadow-[0_8px_24px_rgba(14,165,233,0.25)]',
  danger:    'bg-[linear-gradient(135deg,#f472b6,#fb7185)] text-white shadow-[0_4px_16px_rgba(244,114,182,0.3)] hover:not:disabled:-translate-y-1 hover:not:disabled:shadow-[0_10px_26px_rgba(244,114,182,0.45)] hover:not:disabled:brightness-105 hover:not:disabled:saturate-125',
  'danger-outline': 'bg-white/80 border-rose-300 text-rose-600 shadow-[0_4px_14px_rgba(244,63,94,0.12)] hover:not:disabled:-translate-y-0.5 hover:not:disabled:bg-rose-50 hover:not:disabled:border-rose-400 hover:not:disabled:text-rose-700 hover:not:disabled:shadow-[0_8px_24px_rgba(244,63,94,0.22)]',
}

const sizeClasses = {
  sm: 'px-[18px] py-[8px] text-[0.82rem]',
  md: 'px-[22px] py-[10px] text-[0.88rem]',
  lg: 'px-[28px] py-[13px] text-[0.95rem]',
}

const buttonClass = computed(() => [
  'relative inline-flex items-center justify-center gap-2 rounded-full font-semibold font-base tracking-[-0.01em] cursor-pointer border border-transparent transition-all duration-150 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed active:not:disabled:scale-95',
  sizeClasses[props.size] || sizeClasses.md,
  variantClasses[props.variant] || variantClasses.primary,
  props.full ? 'w-full' : '',
  props.loading ? 'pointer-events-none' : '',
])
</script>

