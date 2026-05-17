<template>
  <div class="field">
    <label v-if="label" :for="inputId" class="field__label">{{ label }}</label>

    <div class="field__wrap" :class="{ 'field__wrap--error': error }">
      <span v-if="$slots.prefix" class="field__icon field__icon--left">
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
        class="field__input"
        :class="{ 'field__input--padded-left': $slots.prefix }"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <span v-if="$slots.suffix" class="field__icon field__icon--right">
        <slot name="suffix" />
      </span>
    </div>

    <p v-if="error" class="field__error">{{ error }}</p>
    <p v-else-if="hint" class="field__hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
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

<style scoped>
.field { display: flex; flex-direction: column; gap: 6px; }

.field__label {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--text-secondary);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.field__wrap {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--color-bg-elevated);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-md);
  overflow: hidden;
  transition: border-color var(--duration-fast) var(--ease-smooth),
              box-shadow var(--duration-fast) var(--ease-smooth);
}
.field__wrap:focus-within {
  border-color: var(--color-violet-500);
  box-shadow: 0 0 0 3px rgba(139,92,246,0.15);
}
.field__wrap--error {
  border-color: #f87171;
}
.field__wrap--error:focus-within {
  box-shadow: 0 0 0 3px rgba(248,113,113,0.15);
}

.field__input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--text-primary);
  padding: 13px 16px;
  font-size: 0.95rem;
  line-height: 1.5;
  width: 100%;
  resize: vertical;
  border-radius: inherit;
  background-clip: padding-box;
}
.field__input:focus-visible { outline: none; }
.field__input:-webkit-autofill,
.field__input:-webkit-autofill:hover,
.field__input:-webkit-autofill:focus,
.field__input:-webkit-autofill:active {
  -webkit-text-fill-color: var(--text-primary);
  caret-color: var(--text-primary);
  -webkit-box-shadow: 0 0 0 1000px var(--color-bg-elevated) inset;
  border-radius: inherit;
  background-clip: padding-box;
  transition: background-color 9999s ease-out 0s;
}
.field__input::placeholder { color: var(--text-muted); }
.field__input--padded-left { padding-left: 44px; }

.field__icon {
  position: absolute;
  display: flex;
  align-items: center;
  color: var(--text-muted);
  pointer-events: none;
}
.field__icon--left  { left: 14px; }
.field__icon--right { right: 14px; }

.field__error { font-size: 0.8rem; color: #f87171; }
.field__hint  { font-size: 0.8rem; color: var(--text-muted); }
</style>
