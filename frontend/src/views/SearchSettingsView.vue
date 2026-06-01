<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useProfileStore } from '@/stores/profile.js'
import { useSubscriptionStore } from '@/stores/subscription.js'
import { useToast } from '@/composables/useToast.js'
import { SELECT_OPTIONS, INTEREST_OPTIONS } from '@/constants/profileOptions.js'
import BaseInput from '@/components/BaseInput.vue'
import BaseButton from '@/components/BaseButton.vue'
import DualRangeSlider from '@/components/DualRangeSlider.vue'
import SingleRangeSlider from '@/components/SingleRangeSlider.vue'
import GlassDropdown from '@/components/GlassDropdown.vue'

const profileStore = useProfileStore()
const subscriptionStore = useSubscriptionStore()
const { toast } = useToast()

const settings = ref({
  minAge: 18,
  maxAge: 40,
  distance: 25,
  gender: 'both',
  datingPurpose: '',
  bodyType: '',
  eyeColor: '',
  hairColor: '',
  smoking: '',
  drinking: '',
  children: '',
  zodiacSign: '',
  exercise: '',
  minHeight: '',
  maxHeight: '',
  minWeight: '',
  maxWeight: '',
  interests: [],
})

const showFiltersContent = computed(() => Boolean(profileStore.filters))
const selectOptions = SELECT_OPTIONS
const interestOptions = INTEREST_OPTIONS
const isPremium = computed(() => subscriptionStore.isPremium)
const isSubscriptionLoading = computed(() => subscriptionStore.isLoading)
const interestQuery = ref('')
const isSaving = ref(false)

const selectedInterests = computed(() => settings.value.interests || [])
const availableInterests = computed(() =>
  interestOptions.filter((opt) => !selectedInterests.value.includes(opt.value))
)
const filteredInterestOptions = computed(() => {
  const query = interestQuery.value.trim().toLowerCase()
  const source = availableInterests.value
  if (!query) return source
  return source
    .filter((opt) => opt.label.toLowerCase().includes(query) || opt.value.includes(query))
})

const syncForm = () => {
  if (!profileStore.filters) return
  settings.value = {
    minAge: profileStore.filters.min_age,
    maxAge: profileStore.filters.max_age,
    distance: profileStore.filters.distance,
    gender: profileStore.filters.gender,
    datingPurpose: profileStore.filters.dating_purpose || '',
    bodyType: profileStore.filters.body_type || '',
    eyeColor: profileStore.filters.eye_color || '',
    hairColor: profileStore.filters.hair_color || '',
    smoking: profileStore.filters.smoking || '',
    drinking: profileStore.filters.drinking || '',
    children: profileStore.filters.children || '',
    zodiacSign: profileStore.filters.zodiac_sign || '',
    exercise: profileStore.filters.exercise || '',
    minHeight: profileStore.filters.min_height ?? '',
    maxHeight: profileStore.filters.max_height ?? '',
    minWeight: profileStore.filters.min_weight ?? '',
    maxWeight: profileStore.filters.max_weight ?? '',
    interests: Array.isArray(profileStore.filters.interests)
      ? [...profileStore.filters.interests]
      : [],
  }
}

onMounted(async () => {
  await subscriptionStore.fetchStatus()
  const result = await profileStore.fetchFilters()
  if (!result?.success) toast.error('Unable to load filters.')
  syncForm()
})

watch(() => profileStore.filters, syncForm, { immediate: true })

const enableAdvanced = computed({
  get: () => profileStore.useAdditionalFilters,
  set: (val) => {
    if (!isPremium.value) return
    profileStore.setUseAdditionalFilters(val, { refresh: false })
  }
})

const isAdvancedLocked = computed(() => !isPremium.value || !enableAdvanced.value)

const toNumber = (value) => {
  if (value === '' || value === null || value === undefined) return null
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

const setIfPresent = (payload, key, value) => {
  if (value === '' || value === null || value === undefined) return
  if (typeof value === 'string') {
    const normalized = value.trim()
    if (!normalized) return
    payload[key] = normalized
    return
  }
  payload[key] = value
}

const addInterest = (value) => {
  if (!isPremium.value) return
  if (selectedInterests.value.includes(value)) return
  if (selectedInterests.value.length >= 10) {
    toast.error('Select up to 10 interests.')
    return
  }
  settings.value.interests = [...selectedInterests.value, value]
}

const removeInterest = (value) => {
  settings.value.interests = selectedInterests.value.filter((item) => item !== value)
}

const goPremium = async () => {
  await subscriptionStore.startCheckout()
}

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

  if (isPremium.value && settings.value.interests?.length > 10) {
    toast.error('Select up to 10 interests.')
    return
  }

  payload.use_advanced_filters = Boolean(enableAdvanced.value)

  if (isPremium.value) {
    setIfPresent(payload, 'dating_purpose', settings.value.datingPurpose)
    setIfPresent(payload, 'body_type', settings.value.bodyType)
    setIfPresent(payload, 'eye_color', settings.value.eyeColor)
    setIfPresent(payload, 'hair_color', settings.value.hairColor)
    setIfPresent(payload, 'smoking', settings.value.smoking)
    setIfPresent(payload, 'drinking', settings.value.drinking)
    setIfPresent(payload, 'children', settings.value.children)
    setIfPresent(payload, 'zodiac_sign', settings.value.zodiacSign)
    setIfPresent(payload, 'exercise', settings.value.exercise)

    const minHeight = toNumber(settings.value.minHeight)
    const maxHeight = toNumber(settings.value.maxHeight)
    const minWeight = toNumber(settings.value.minWeight)
    const maxWeight = toNumber(settings.value.maxWeight)

    if (minHeight !== null) payload.min_height = minHeight
    if (maxHeight !== null) payload.max_height = maxHeight
    if (minWeight !== null) payload.min_weight = minWeight
    if (maxWeight !== null) payload.max_weight = maxWeight

    if (Array.isArray(settings.value.interests) && settings.value.interests.length) {
      payload.interests = settings.value.interests
    }
  }

  isSaving.value = true
  try {
    const result = await profileStore.updateFilters(payload)
    if (result.success) {
      toast.success('Filters updated.')
    } else {
      toast.error(result.message || 'Unable to update filters.')
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="min-h-screen text-slate-900">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">

      <div class="flex flex-col gap-8">
        <!-- Basic Filters -->
        <section class="glass-panel" style="padding: 28px;">
          <div class="mb-8 flex flex-col gap-2 border-b border-slate-200/50 pb-5">
            <h2 class="text-2xl font-bold text-slate-950">Discovery Settings</h2>
            <p class="text-[0.9rem] text-slate-500">Set your basic preferences for who you want to see.</p>
          </div>

          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="flex flex-col gap-6 md:col-span-2">
              <div class="p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
                <SingleRangeSlider v-model="settings.distance" :min="1" :max="200" label="Maximum Distance" suffix=" km" />
              </div>

              <div class="p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
                <DualRangeSlider v-model:model-min="settings.minAge" v-model:model-max="settings.maxAge" :min="18" :max="80" label="Age Range" />
              </div>
            </div>

            <div class="flex flex-col justify-between gap-6 p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <div class="space-y-3">
                <label for="search-gender" class="text-[0.95rem] font-bold text-slate-800">Show Me</label>
                <GlassDropdown v-model="settings.gender" :options="[{value:'both', label:'Everyone'}, {value:'woman', label:'Women'}, {value:'man', label:'Men'}]" placeholder="Everyone" :showEmpty="false" />
              </div>

              <div class="pt-4">
                <BaseButton @click="save" variant="primary" full :loading="isSaving" :disabled="!showFiltersContent || isSaving">
                  Apply Filters
                </BaseButton>
              </div>
            </div>
          </div>
        </section>

        <!-- Premium Filters -->
        <section v-if="isPremium" class="glass-panel" style="padding: 28px;">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4" :class="{'mb-8 border-b border-slate-200/50 pb-5': enableAdvanced}">
            <div class="flex flex-col">
              <h2 class="text-2xl font-bold text-slate-950">Premium Filters</h2>
              <p class="text-[0.9rem] text-slate-500 mt-1">Refine your search by lifestyle, appearance and interests</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center shrink-0">
              <input type="checkbox" class="peer sr-only" v-model="enableAdvanced" />
              <div class="h-8 w-14 rounded-full border border-slate-300 bg-slate-200 transition duration-300 peer-checked:bg-gradient-to-r peer-checked:from-amber-400 peer-checked:to-yellow-500 peer-checked:border-transparent shadow-inner"></div>
              <div class="pointer-events-none absolute left-1 top-1 h-6 w-6 rounded-full bg-white shadow-sm transition-all duration-300 peer-checked:translate-x-6"></div>
            </label>
          </div>

          <div v-if="enableAdvanced" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 animate-fade-in-up" style="align-items: stretch;">

            <!-- Lifestyle -->
            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <div class="mb-5 flex items-center justify-between">
                <h3 class="text-[1.1rem] font-bold text-slate-900">Lifestyle</h3>
              </div>
              <div class="flex flex-col gap-4">
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Dating purpose</label>
                  <GlassDropdown v-model="settings.datingPurpose" :options="selectOptions.datingPurpose" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Smoking</label>
                  <GlassDropdown v-model="settings.smoking" :options="selectOptions.smoking" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Drinking</label>
                  <GlassDropdown v-model="settings.drinking" :options="selectOptions.drinking" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Exercise</label>
                  <GlassDropdown v-model="settings.exercise" :options="selectOptions.exercise" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Zodiac sign</label>
                  <GlassDropdown v-model="settings.zodiacSign" :options="selectOptions.zodiacSign" placeholder="Any" empty-label="Any" />
                </div>
              </div>
            </section>

            <!-- Appearance -->
            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <div class="mb-5 flex items-center justify-between">
                <h3 class="text-[1.1rem] font-bold text-slate-900">Appearance</h3>
              </div>
              <div class="flex flex-col gap-4">
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Body type</label>
                  <GlassDropdown v-model="settings.bodyType" :options="selectOptions.bodyType" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Eye color</label>
                  <GlassDropdown v-model="settings.eyeColor" :options="selectOptions.eyeColor" placeholder="Any" empty-label="Any" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[0.85rem] font-semibold text-slate-700">Hair color</label>
                  <GlassDropdown v-model="settings.hairColor" :options="selectOptions.hairColor" placeholder="Any" empty-label="Any" />
                </div>
                <div class="pt-3 border-t border-slate-200/50 mt-1">
                  <DualRangeSlider v-model:model-min="settings.minHeight" v-model:model-max="settings.maxHeight" :min="70" :max="250" label="Height" suffix=" cm" />
                </div>
                <div class="pt-1">
                  <DualRangeSlider v-model:model-min="settings.minWeight" v-model:model-max="settings.maxWeight" :min="20" :max="200" label="Weight" suffix=" kg" />
                </div>
              </div>
            </section>

            <!-- Interests -->
            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm md:col-span-2 lg:col-span-1">
              <div class="mb-5 flex items-center justify-between">
                <h3 class="text-[1.1rem] font-bold text-slate-900">Interests</h3>
                <span class="text-[0.75rem] font-bold px-2.5 py-1 bg-white border border-slate-200 rounded-full text-slate-600 shadow-sm">{{ selectedInterests.length }}/10</span>
              </div>

              <div class="mb-5 flex flex-wrap gap-2">
                <template v-if="selectedInterests.length">
                  <button v-for="interest in selectedInterests" :key="interest" type="button" class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-gradient-to-r from-amber-50 to-yellow-50 px-3 py-1.5 text-[0.85rem] font-semibold text-amber-800 transition hover:from-amber-100 hover:to-yellow-100 shadow-sm" @click="removeInterest(interest)">
                    <span>{{ interestOptions.find((opt) => opt.value === interest)?.label || interest }}</span>
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-200/80 text-[10px] font-bold text-amber-900">×</span>
                  </button>
                </template>
                <p v-else class="text-[0.85rem] text-slate-500 italic">No interests selected.</p>
              </div>

              <div class="relative mb-4">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input v-model="interestQuery" type="text" placeholder="Search interests..." class="w-full rounded-xl border border-slate-300/80 bg-white/70 py-2.5 pl-9 pr-3 text-[0.85rem] text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-[3px] focus:ring-amber-400/10" />
              </div>

              <div class="flex flex-col gap-1.5 overflow-y-auto pr-1 custom-scrollbar" style="max-height: 280px;">
                <button v-for="opt in filteredInterestOptions" :key="opt.value" type="button" class="rounded-xl border border-transparent bg-white/60 px-4 py-2.5 text-left text-[0.85rem] font-medium text-slate-700 transition hover:border-amber-200 hover:bg-white hover:shadow-sm" @click="addInterest(opt.value)">
                  {{ opt.label }}
                </button>
                <p v-if="!filteredInterestOptions.length" class="p-2 text-[0.85rem] text-slate-500 text-center">No matching interests.</p>
              </div>
            </section>

          </div>
        </section>

        <!-- Premium Upsell -->
        <section v-else class="glass-panel relative overflow-hidden" style="padding: 32px;">
          <div class="relative z-10 flex flex-col items-center text-center">
            <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 shadow-[0_8px_16px_rgba(245,158,11,0.3)] border-2 border-white/50">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Unlock Premium Filters</h2>
            <p class="mt-3 text-[1rem] text-slate-600 max-w-md leading-relaxed">
              Find exactly who you're looking for by filtering lifestyle habits, appearance, and shared interests.
            </p>
            <div class="mt-8 w-full sm:w-auto min-w-[240px]">
              <button @click="goPremium" :disabled="isSubscriptionLoading" class="relative flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 py-3.5 px-6 text-[1rem] font-bold text-white shadow-lg transition hover:from-slate-800 hover:to-slate-700 hover:shadow-xl active:scale-[0.98]">
                <span>Get Premium</span>
              </button>
            </div>
          </div>
          <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-gradient-to-bl from-amber-400/20 to-yellow-500/5 blur-3xl"></div>
          <div class="pointer-events-none absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-gradient-to-tr from-amber-400/20 to-yellow-500/5 blur-3xl"></div>
        </section>
      </div>
    </div>
  </div>
</template>