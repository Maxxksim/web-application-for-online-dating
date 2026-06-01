<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSubscriptionStore } from '@/stores/subscription.js'
import { useProfileStore } from '@/stores/profile.js'
import BaseButton from '@/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const subscriptionStore = useSubscriptionStore()
const profileStore = useProfileStore()

const isLoading = ref(true)

const status = computed(() => String(route.params.status || '').toLowerCase())
const plan = computed(() => String(route.query.plan || 'premium'))

const isSuccess = computed(() => status.value === 'success')
const isCancel = computed(() => status.value === 'cancel')

const title = computed(() => {
  if (isSuccess.value) return 'Premium activated'
  if (isCancel.value) return 'Subscription cancelled'
  return 'Subscription update'
})

const description = computed(() => {
  if (isSuccess.value) {
    return subscriptionStore.isPremium
      ? 'Your Premium membership is active. Enjoy advanced filters.'
      : 'We are finalizing your Premium status. This may take a moment.'
  }
  if (isCancel.value) {
    return 'Your Premium subscription has been cancelled.'
  }
  return 'Return to your profile to continue.'
})

const goToFilters = () => router.push({ name: 'search-settings' })
const goToProfile = () => router.push({ name: 'profile' })

onMounted(async () => {
  if (!isSuccess.value && !isCancel.value) {
    router.replace({ name: 'profile' })
    return
  }

  await subscriptionStore.fetchStatus(plan.value)
  // Sync filters state so useAdditionalFilters and premium indicators are up to date
  await profileStore.fetchFilters()
  isLoading.value = false
})
</script>

<template>
  <div class="page items-center">
    <div class="glass-panel w-[min(520px,100%)] p-7 flex flex-col gap-3">
      <div class="flex justify-between items-center">
        <span class="chip">{{ plan }}</span>
        <p class="m-0 text-[0.8rem] uppercase tracking-[0.14em] text-slate-500 font-bold">{{ isSuccess ? 'Success' : 'Cancelled' }}</p>
      </div>

      <h1 class="m-0 text-[1.6rem] font-bold text-slate-900">{{ title }}</h1>
      <p class="m-0 text-[0.95rem] text-slate-700">
        {{ description }}
      </p>

      <div v-if="isLoading" class="text-[0.85rem] text-slate-500">Checking status...</div>

      <div class="grid gap-2.5 mt-2.5">
        <BaseButton
          v-if="isSuccess"
          variant="primary"
          full
          @click="goToFilters"
        >
          Go to Filters
        </BaseButton>
        <BaseButton
          variant="secondary"
          full
          @click="goToProfile"
        >
          Back to Profile
        </BaseButton>
      </div>
    </div>
  </div>
</template>

