<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useProfileStore } from '@/stores/profile.js'
import { useToast } from '@/composables/useToast.js'
import BaseInput from '@/components/BaseInput.vue'
import BaseButton from '@/components/BaseButton.vue'

const profileStore = useProfileStore()
const { toast } = useToast()

const settings = ref({
  minAge: 18,
  maxAge: 40,
  distance: 25,
  gender: 'both',
})

const isLoading = computed(() => profileStore.isLoadingFilters)

const syncForm = () => {
  if (!profileStore.filters) return
  settings.value = {
    minAge: profileStore.filters.min_age,
    maxAge: profileStore.filters.max_age,
    distance: profileStore.filters.distance,
    gender: profileStore.filters.gender,
  }
}

onMounted(async () => {
  const result = await profileStore.fetchFilters()
  if (!result?.success) {
    toast.error('Unable to load filters.')
  }
  syncForm()
})

watch(() => profileStore.filters, syncForm)

const save = async () => {
  const payload = {
    min_age: Number(settings.value.minAge),
    max_age: Number(settings.value.maxAge),
    distance: Number(settings.value.distance),
    gender: settings.value.gender,
  }

  if (Number.isNaN(payload.min_age) || Number.isNaN(payload.max_age)) {
    toast.error('Please enter valid ages.')
    return
  }

  if (payload.min_age >= payload.max_age) {
    toast.error('Min age must be lower than max age.')
    return
  }

  if (!payload.distance || payload.distance < 1) {
    toast.error('Distance must be at least 1 km.')
    return
  }

  const result = await profileStore.updateFilters(payload)
  if (result.success) {
    toast.success('Filters updated.')
  } else {
    toast.error(result.message || 'Unable to update filters.')
  }
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <p class="eyebrow">Filters</p>
      <h1 class="page-title">Discovery settings</h1>
    </div>

    <div class="glass-panel p-6 md:p-8 max-w-lg">
      <div v-if="isLoading" class="text-sm text-white/60">
        Loading filters...
      </div>
      <div v-else class="space-y-6">
        <div class="glass-panel glass-panel--tight p-4">
          <label class="block text-xs uppercase tracking-[0.3em] text-white/70 mb-2">Maximum Distance</label>
          <p class="text-lg font-semibold text-white mb-3">{{ settings.distance }} km</p>
          <input v-model="settings.distance" type="range" min="1" max="200" class="w-full h-2 rounded-lg appearance-none cursor-pointer input-range" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="settings.minAge" type="number" label="Min Age" />
          <BaseInput v-model="settings.maxAge" type="number" label="Max Age" />
        </div>

        <div>
          <label class="block text-xs uppercase tracking-[0.3em] text-white/70 mb-2" for="gender">Gender</label>
          <select
            id="gender"
            v-model="settings.gender"
            class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-violet-400"
          >
            <option value="both">Everyone</option>
            <option value="woman">Women</option>
            <option value="man">Men</option>
          </select>
        </div>

        <BaseButton @click="save" variant="primary" full :loading="isLoading">
          Update Settings
        </BaseButton>
      </div>
    </div>
  </div>
</template>
