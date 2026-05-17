/**
 * composables/useToast.js
 * Lightweight toast notification system.
 * Usage: const { toast } = useToast()
 *        toast.success('Saved!') / toast.error('Oops') / toast.info('FYI')
 */

import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

const DURATION = 3500

export function useToast() {
  function add(message, type = 'info') {
    const id = ++nextId
    toasts.value.push({ id, message, type })
    setTimeout(() => remove(id), DURATION)
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  return {
    toasts,
    remove,
    toast: {
      success: (msg) => add(msg, 'success'),
      error:   (msg) => add(msg, 'error'),
      info:    (msg) => add(msg, 'info'),
    },
  }
}
