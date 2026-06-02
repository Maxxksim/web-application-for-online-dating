<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSubscriptionStore } from '@/stores/subscription.js'
import BaseButton from '@/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const subscriptionStore = useSubscriptionStore()

const isLoading = ref(true)

const status = computed(() => String(route.params.status || '').toLowerCase())
const plan = computed(() => String(route.query.plan || 'premium'))

const isSuccess = computed(() => status.value === 'success')
const isCancel = computed(() => status.value === 'cancel')

const title = computed(() => {
  if (isSuccess.value) return subscriptionStore.isPremium ? '🎉 Premium activated!' : 'Processing...'
  if (isCancel.value) return 'Payment cancelled'
  return 'Subscription update'
})

const description = computed(() => {
  if (isSuccess.value) {
    return subscriptionStore.isPremium
      ? 'Your Premium membership is now active. You have access to advanced filters, profile boost in discovery, and swipe rollback.'
      : 'We are finalizing your Premium status. This may take a moment — try refreshing your profile.'
  }
  if (isCancel.value) {
    return 'You cancelled the payment process. Your subscription has not changed. You can try again anytime.'
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
  isLoading.value = false
})
</script>

<template>
  <div class="page items-center">
    <div class="glass-panel w-[min(520px,100%)] p-7 flex flex-col gap-4">
      <div class="flex justify-between items-center">
        <span class="chip">{{ plan }}</span>
        <p class="m-0 text-[0.8rem] uppercase tracking-[0.14em] font-bold"
           :class="isSuccess ? 'text-emerald-600' : 'text-slate-500'">
          {{ isSuccess ? 'Success' : 'Cancelled' }}
        </p>
      </div>

      <div v-if="isLoading" class="flex flex-col gap-2">
        <div class="h-8 w-48 rounded-xl bg-slate-200/70 animate-pulse" />
        <div class="h-4 w-full rounded-xl bg-slate-200/70 animate-pulse" />
        <div class="h-4 w-3/4 rounded-xl bg-slate-200/70 animate-pulse" />
      </div>

      <template v-else>
        <div class="flex items-center gap-3">
          <div class="text-3xl">
            {{ isSuccess && subscriptionStore.isPremium ? '✨' : isSuccess ? '⏳' : '↩️' }}
          </div>
          <h1 class="m-0 text-[1.5rem] font-bold text-slate-900">{{ title }}</h1>
        </div>

        <p class="m-0 text-[0.95rem] text-slate-700 leading-relaxed">
          {{ description }}
        </p>

        <div v-if="isSuccess && subscriptionStore.isPremium" class="rounded-2xl border border-cyan-200/60 bg-cyan-50/60 p-4 flex flex-col gap-2">
          <p class="m-0 text-[0.78rem] font-bold uppercase tracking-[0.08em] text-cyan-600">What you unlocked</p>
          <ul class="m-0 pl-4 flex flex-col gap-1 text-[0.9rem] text-slate-700">
            <li>Advanced search filters</li>
            <li>Profile boost in discovery</li>
            <li>Swipe rollback</li>
            <li>Deeper matches</li>
          </ul>
        </div>
      </template>

      <div class="grid gap-2.5 mt-1">
        <BaseButton
          v-if="isSuccess"
          variant="primary"
          full
          @click="goToFilters"
        >
          Go to Filters
        </BaseButton>
        <BaseButton
          v-if="isCancel"
          variant="primary"
          full
          @click="goToProfile"
        >
          Try Again
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