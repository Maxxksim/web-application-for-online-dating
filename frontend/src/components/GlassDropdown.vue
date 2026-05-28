<template>
  <div class="relative w-full" ref="dropdownRef" @keydown.esc="isOpen = false">
    <div 
      class="flex items-center gap-2 w-full bg-white/65 backdrop-blur-md border rounded-[14px] px-[14px] py-[12px] text-[0.88rem] font-medium text-slate-800 cursor-pointer transition-[border-color,box-shadow,background-color] duration-[180ms] ease-out select-none outline-none hover:border-cyan-300/50 hover:bg-white/80" 
      :class="isOpen ? 'border-cyan-300 ring-[3px] ring-cyan-400/15 bg-white/90' : 'border-slate-300/60 focus:border-cyan-300 focus:ring-[3px] focus:ring-cyan-400/15 focus:bg-white/90'" 
      @click="toggle"
      tabindex="0"
    >
      <span class="flex-1 whitespace-nowrap overflow-hidden text-ellipsis" :class="[center ? 'text-center' : 'text-left', !selectedOption ? 'text-slate-400' : '']">
        {{ selectedOption ? selectedOption.label : placeholder }}
      </span>
      <svg class="shrink-0 w-4 h-4 text-slate-400 transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m6 9 6 6 6-6"/>
      </svg>
    </div>

    <Transition name="dropdown-fade">
      <div v-if="isOpen" class="absolute top-[calc(100%+6px)] left-0 w-full z-50 bg-white/85 backdrop-blur-xl backdrop-saturate-200 border border-slate-300/70 rounded-2xl shadow-[0_12px_40px_rgba(15,23,42,0.12)] p-[6px]">
        <div class="max-h-[220px] overflow-y-auto flex flex-col gap-[2px] dropdown-scroll">
          <div 
            v-if="showEmpty"
            class="px-[14px] py-[10px] rounded-[10px] text-[0.88rem] font-medium cursor-pointer transition-all duration-150 select-none" 
            :class="modelValue === '' ? 'bg-cyan-400/25 text-cyan-600 font-semibold' : 'text-slate-700 hover:bg-cyan-400/15 hover:text-cyan-600'"
            @click="select('')"
          >
            {{ emptyLabel }}
          </div>
          <div 
            v-for="opt in options" 
            :key="opt.value"
            class="px-[14px] py-[10px] rounded-[10px] text-[0.88rem] font-medium cursor-pointer transition-all duration-150 select-none"
            :class="modelValue === opt.value ? 'bg-cyan-400/25 text-cyan-600 font-semibold' : 'text-slate-700 hover:bg-cyan-400/15 hover:text-cyan-600'"
            @click="select(opt.value)"
          >
            {{ opt.label }}
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select...' },
  showEmpty: { type: Boolean, default: true },
  emptyLabel: { type: String, default: 'Empty' },
  center: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const dropdownRef = ref(null)

const selectedOption = computed(() => {
  return props.options.find(opt => String(opt.value) === String(props.modelValue))
})

const toggle = () => {
  isOpen.value = !isOpen.value
}

const select = (val) => {
  emit('update:modelValue', val)
  isOpen.value = false
}

const onClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>
