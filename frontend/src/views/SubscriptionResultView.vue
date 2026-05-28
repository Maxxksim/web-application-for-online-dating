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
  isLoading.value = false
})
</script>

<template>
  <div class="page subscription-page">
    <div class="glass-panel subscription-panel">
      <div class="subscription-panel__header">
        <span class="chip">{{ plan }}</span>
        <p class="subscription-panel__status">{{ isSuccess ? 'Success' : 'Cancelled' }}</p>
      </div>

      <h1 class="subscription-panel__title">{{ title }}</h1>
      <p class="subscription-panel__text">
        {{ description }}
      </p>

      <div v-if="isLoading" class="subscription-panel__loading">Checking status...</div>

      <div class="subscription-panel__actions">
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

<style scoped>
.subscription-page {
  align-items: center;
}

.subscription-panel {
  width: min(520px, 100%);
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.subscription-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.subscription-panel__status {
  margin: 0;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: var(--text-muted);
  font-weight: 700;
}

.subscription-panel__title {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--text-primary);
}

.subscription-panel__text {
  margin: 0;
  font-size: 0.95rem;
  color: var(--text-secondary);
}

.subscription-panel__loading {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.subscription-panel__actions {
  display: grid;
  gap: 10px;
  margin-top: 10px;
}
</style>
