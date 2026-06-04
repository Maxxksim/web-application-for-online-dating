<template>
  <div class="flex flex-col gap-2.5">
    <div class="flex items-center justify-between">
      <span class="text-[0.82rem] font-semibold text-slate-700">{{ label }}</span>
      <div class="flex items-center gap-2">
        <span class="text-[0.82rem] font-bold text-slate-900">
          {{ displayLabel }}
        </span>
        <button
          v-if="nullable && !isNotSet"
          type="button"
          class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-600 hover:bg-slate-300 transition"
          @click="reset"
        >×</button>
      </div>
    </div>
    <div class="relative h-[6px] py-[14px]">
      <div class="absolute top-1/2 left-0 right-0 h-[6px] -translate-y-1/2 rounded-full bg-cyan-100/70"></div>
      <div
        class="absolute top-1/2 h-[6px] -translate-y-1/2 rounded-full transition-all duration-100 ease-linear"
        :class="isNotSet ? 'bg-slate-200' : 'bg-gradient-to-r from-cyan-300 to-cyan-200'"
        :style="fillStyle"
      ></div>
      <input
        type="range"
        class="dual-range-input absolute top-1/2 left-0 w-full h-[6px] -translate-y-1/2 appearance-none bg-transparent pointer-events-none m-0"
        :min="min"
        :max="max"
        :step="step"
        :value="innerMin"
        @input="onMinInput"
      />
      <input
        type="range"
        class="dual-range-input absolute top-1/2 left-0 w-full h-[6px] -translate-y-1/2 appearance-none bg-transparent pointer-events-none m-0"
        :min="min"
        :max="max"
        :step="step"
        :value="innerMax"
        @input="onMaxInput"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelMin: { type: [Number, String], default: null },
  modelMax: { type: [Number, String], default: null },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  label: { type: String, default: '' },
  suffix: { type: String, default: '' },
  nullable: { type: Boolean, default: false },
  notSetLabel: { type: String, default: 'Not set' },
})

const emit = defineEmits(['update:modelMin', 'update:modelMax'])

const isNullMin = computed(() => props.modelMin === null || props.modelMin === undefined)
const isNullMax = computed(() => props.modelMax === null || props.modelMax === undefined)
const isNotSet = computed(() => props.nullable && isNullMin.value && isNullMax.value)

const innerMin = computed(() => {
  if (isNullMin.value) return props.min
  const val = Number(props.modelMin)
  return isNaN(val) ? props.min : val
})

const innerMax = computed(() => {
  if (isNullMax.value) return props.max
  const val = Number(props.modelMax)
  return isNaN(val) ? props.max : val
})

const displayLabel = computed(() => {
  if (isNotSet.value) return props.notSetLabel
  const minPart = isNullMin.value ? '?' : `${innerMin.value}${props.suffix}`
  const maxPart = isNullMax.value ? '?' : `${innerMax.value}${props.suffix}`
  return `${minPart} — ${maxPart}`
})

const fillStyle = computed(() => {
  const range = props.max - props.min || 1
  const cMin = Math.max(props.min, Math.min(props.max, innerMin.value))
  const cMax = Math.max(props.min, Math.min(props.max, innerMax.value))
  const left = ((cMin - props.min) / range) * 100
  const right = ((cMax - props.min) / range) * 100
  return { left: `${left}%`, width: `${Math.max(0, right - left)}%` }
})

const onMinInput = (e) => {
  let val = Number(e.target.value)
  const currentMax = innerMax.value
  if (val >= currentMax) {
    val = currentMax - props.step
    e.target.value = val
  }
  emit('update:modelMin', val)
}

const onMaxInput = (e) => {
  let val = Number(e.target.value)
  const currentMin = innerMin.value
  if (val <= currentMin) {
    val = currentMin + props.step
    e.target.value = val
  }
  emit('update:modelMax', val)
}

const reset = () => {
  emit('update:modelMin', null)
  emit('update:modelMax', null)
}
</script>