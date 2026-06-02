<template>
  <div class="flex flex-col gap-2.5">
    <div class="flex items-center justify-between">
      <span class="text-[0.82rem] font-semibold text-slate-700">{{ label }}</span>
      <div class="flex items-center gap-2">
        <span v-if="isEmpty" class="text-[0.82rem] font-bold text-slate-400">Not set</span>
        <span v-else class="text-[0.82rem] font-bold text-slate-900">{{ displayValue }}{{ suffix }}</span>
        <button v-if="!isEmpty" type="button" @click="clearValue" class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] text-slate-500 transition-colors hover:bg-slate-300" aria-label="Clear value">×</button>
      </div>
    </div>
    <div class="relative h-[6px] py-[14px]">
      <div class="absolute top-1/2 left-0 right-0 h-[6px] -translate-y-1/2 rounded-full bg-cyan-100/70"></div>
      <div
        class="absolute top-1/2 left-0 h-[6px] -translate-y-1/2 rounded-full bg-gradient-to-r from-cyan-300 to-cyan-200 transition-all duration-100 ease-linear"
        :style="fillStyle"
      ></div>
      <input
        type="range"
        class="dual-range-input absolute top-1/2 left-0 w-full h-[6px] -translate-y-1/2 appearance-none bg-transparent m-0"
        :min="min"
        :max="max"
        :step="step"
        :value="displayValue"
        @input="onInput"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [Number, String], default: '' },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  label: { type: String, default: '' },
  suffix: { type: String, default: '' },
  fallback: { type: Number, default: null },
})

const emit = defineEmits(['update:modelValue'])

const isEmpty = computed(() => {
  return props.modelValue === '' || props.modelValue === null || Number(props.modelValue) === 0
})

const displayValue = computed(() => {
  if (!isEmpty.value) {
    const val = Number(props.modelValue)
    if (Number.isFinite(val)) return val
  }
  if (Number.isFinite(props.fallback)) return props.fallback
  return props.min
})

const fillStyle = computed(() => {
  const range = props.max - props.min || 1
  const clamped = Math.max(props.min, Math.min(props.max, displayValue.value))
  const percent = ((clamped - props.min) / range) * 100
  return { width: `${percent}%` }
})

const onInput = (e) => {
  const val = Number(e.target.value)
  emit('update:modelValue', val)
}

const clearValue = () => {
  emit('update:modelValue', '')
}
</script>
