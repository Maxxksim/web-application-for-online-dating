import { defineStore } from 'pinia'
import { ref } from 'vue'
import { subscriptionApi } from '@/api/subscription.js'

const DEFAULT_PLAN = 'premium'


export const useSubscriptionStore = defineStore('subscription', () => {
  const isPremium = ref(false)
  const isCanceled = ref(false)
  const endsAt = ref(null)
  const isLoading = ref(false)
  const error = ref('')

  async function fetchStatus(plan = DEFAULT_PLAN) {
    isLoading.value = true
    error.value = ''
    try {
      const { data } = await subscriptionApi.status(plan)
      isPremium.value = Boolean(data?.is_active)
      isCanceled.value = Boolean(data?.is_canceled)
      endsAt.value = data?.ends_at ?? null
      return { success: true, isPremium: isPremium.value }
    } catch {
      error.value = 'Unable to load subscription status.'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function startCheckout(plan = DEFAULT_PLAN) {
    isLoading.value = true
    error.value = ''
    try {
      const { data } = await subscriptionApi.checkout(plan)
      if (data?.checkout_url) {
        window.location.href = data.checkout_url
        return { success: true, redirect: true }
      }
      error.value = 'Checkout unavailable.'
      return { success: false }
    } catch {
      error.value = 'Unable to start checkout.'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function cancel(plan = DEFAULT_PLAN) {
    isLoading.value = true
    error.value = ''
    try {
      const { data } = await subscriptionApi.cancel(plan)
      isPremium.value = false
      isCanceled.value = true
      endsAt.value = data?.ends_at ?? null
      return { success: true }
    } catch {
      error.value = 'Unable to cancel subscription.'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function resume(plan = DEFAULT_PLAN) {
    isLoading.value = true
    error.value = ''
    try {
      await subscriptionApi.resume(plan)
      isPremium.value = true
      isCanceled.value = false
      endsAt.value = null
      return { success: true }
    } catch {
      error.value = 'Unable to resume subscription.'
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }



  return {
    isPremium,
    isCanceled,
    endsAt,
    isLoading,
    error,
    fetchStatus,
    startCheckout,
    cancel,
    resume,
  }
})