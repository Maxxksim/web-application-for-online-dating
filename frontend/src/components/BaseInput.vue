<template>
  <div class="flex flex-col gap-[6px]">
    <label v-if="label" :for="inputId" class="text-[0.82rem] font-semibold text-slate-700">
      {{ label }}
    </label>

    <div
      class="relative flex items-center rounded-[14px] border bg-white/65 backdrop-blur-md transition-[border-color,box-shadow] duration-150"
      :class="[
        error 
          ? 'border-rose-500 focus-within:ring-[3px] focus-within:ring-rose-500/10' 
          : 'border-slate-300/90 focus-within:border-cyan-500 focus-within:ring-[3px] focus-within:ring-cyan-500/10'
      ]"
    >
      <span v-if="$slots.prefix" class="absolute left-3 flex items-center text-slate-500 pointer-events-none">
        <slot name="prefix" />
      </span>

      <component
        :is="type === 'textarea' ? 'textarea' : 'input'"
        :id="inputId"
        v-bind="$attrs"
        :type="type !== 'textarea' ? type : undefined"
        :autocomplete="autocomplete"
        autocapitalize="off"
        autocorrect="off"
        spellcheck="false"
        :value="modelValue"
        :rows="type === 'textarea' ? rows : undefined"
        class="w-full border-none bg-transparent px-[14px] py-[11px] text-[0.9rem] text-slate-900 outline-none rounded-[14px] placeholder:text-slate-500"
        :class="[
          $slots.prefix ? 'pl-9' : '',
          $slots.suffix ? 'pr-9' : '',
          type === 'textarea' ? 'resize-y min-h-[100px]' : '',
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <span v-if="$slots.suffix" class="absolute right-3 flex items-center text-slate-500 pointer-events-none">
        <slot name="suffix" />
      </span>
    </div>

    <p v-if="error" class="m-0 text-[0.8rem] text-rose-500">{{ error }}</p>
    <p v-else-if="hint" class="m-0 text-[0.8rem] text-slate-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: { type: [String, Number], default: '' },
  label:      { type: String, default: '' },
  type:       { type: String, default: 'text' },
  rows:       { type: Number, default: 4 },
  autocomplete: { type: String, default: 'off' },
  error:      { type: String, default: '' },
  hint:       { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const inputId = computed(() => `field-${Math.random().toString(36).slice(2)}`)
</script>

