<template>
  <div class="flex flex-col gap-2.5">
    <div class="flex items-center justify-between">
      <span class="text-[0.82rem] font-semibold text-slate-700">{{ label }}</span>
      <span class="text-[0.82rem] font-bold text-slate-900">{{ displayMin }}{{ suffix }} — {{ displayMax }}{{ suffix }}</span>
    </div>
    <div class="relative h-[6px] py-[14px]">
      <div class="absolute top-1/2 left-0 right-0 h-[6px] -translate-y-1/2 rounded-full bg-cyan-100/70"></div>
      <div class="absolute top-1/2 h-[6px] -translate-y-1/2 rounded-full bg-gradient-to-r from-cyan-300 to-cyan-200 transition-all duration-100 ease-linear" :style="fillStyle"></div>
      <input
        type="range"
        class="dual-range-input absolute top-1/2 left-0 w-full h-[6px] -translate-y-1/2 appearance-none bg-transparent pointer-events-none m-0"
        :min="min"
        :max="max"
        :step="step"
        :value="displayMin"
        @input="onMinInput"
      />
      <input
        type="range"
        class="dual-range-input absolute top-1/2 left-0 w-full h-[6px] -translate-y-1/2 appearance-none bg-transparent pointer-events-none m-0"
        :min="min"
        :max="max"
        :step="step"
        :value="displayMax"
        @input="onMaxInput"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelMin: { type: [Number, String], default: 0 },
  modelMax: { type: [Number, String], default: 100 },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  label: { type: String, default: '' },
  suffix: { type: String, default: '' },
})

const emit = defineEmits(['update:modelMin', 'update:modelMax'])

const displayMin = computed(() => {
  const val = Number(props.modelMin)
  return isNaN(val) || props.modelMin === '' ? props.min : val
})
const displayMax = computed(() => {
  const val = Number(props.modelMax)
  return isNaN(val) || props.modelMax === '' ? props.max : val
})

const fillStyle = computed(() => {
  const range = props.max - props.min || 1
  const cMin = Math.max(props.min, Math.min(props.max, displayMin.value))
  const cMax = Math.max(props.min, Math.min(props.max, displayMax.value))
  
  const left = ((cMin - props.min) / range) * 100
  const right = ((cMax - props.min) / range) * 100
  return { left: `${left}%`, width: `${Math.max(0, right - left)}%` }
})

const onMinInput = (e) => {
  let val = Number(e.target.value)
  if (val >= displayMax.value) {
    val = displayMax.value - props.step
    e.target.value = val
  }
  emit('update:modelMin', val)
}

const onMaxInput = (e) => {
  let val = Number(e.target.value)
  if (val <= displayMin.value) {
    val = displayMin.value + props.step
    e.target.value = val
  }
  emit('update:modelMax', val)
}
</script>
