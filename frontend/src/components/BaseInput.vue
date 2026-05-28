<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="inputId" class="text-sm font-medium text-slate-600">
      {{ label }}
    </label>

    <div
      class="relative flex items-center rounded-2xl border bg-white transition focus-within:ring-2 focus-within:ring-cyan-500 focus-within:ring-offset-2 focus-within:ring-offset-slate-50"
      :class="error ? 'border-rose-500' : 'border-slate-200'"
    >
      <span v-if="$slots.prefix" class="pointer-events-none absolute left-3 flex items-center text-slate-400">
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
        class="w-full resize-y rounded-2xl border-0 bg-transparent px-4 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:outline-none"
        :class="[
          $slots.prefix ? 'pl-10' : '',
          $slots.suffix ? 'pr-10' : '',
          type === 'textarea' ? 'min-h-28' : '',
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <span v-if="$slots.suffix" class="pointer-events-none absolute right-3 flex items-center text-slate-400">
        <slot name="suffix" />
      </span>
    </div>

    <p v-if="error" class="mt-0.5 text-sm text-rose-500">
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-0.5 text-sm text-slate-500">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  rows: { type: Number, default: 4 },
  autocomplete: { type: String, default: 'off' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const inputId = computed(() => `field-${Math.random().toString(36).slice(2)}`)
</script>
