<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
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

const isReady = ref(false)
let _saveTimer = null

const isDraggingSlider = ref(false)

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
  minHeight: null,
  maxHeight: null,
  minWeight: null,
  maxWeight: null,
  interests: [],
})

const selectOptions = SELECT_OPTIONS
const interestOptions = INTEREST_OPTIONS

const showFiltersContent = computed(() => Boolean(profileStore.filters))
const isPremium = computed(() => subscriptionStore.isPremium)
const isSubscriptionLoading = computed(() => subscriptionStore.isLoading)

const interestQuery = ref('')
const isSaving = ref(false)

const selectedInterests = computed(() => settings.value.interests ?? [])

const availableInterests = computed(() =>
  interestOptions.filter((opt) => !selectedInterests.value.includes(opt.value))
)

const filteredInterestOptions = computed(() => {
  const query = interestQuery.value.trim().toLowerCase()
  if (!query) return availableInterests.value
  return availableInterests.value.filter(
    (opt) => opt.label.toLowerCase().includes(query) || opt.value.includes(query)
  )
})

const syncForm = () => {
  const f = profileStore.filters
  if (!f) return

  isReady.value = false

  settings.value = {
    minAge: f.min_age ?? 18,
    maxAge: f.max_age ?? 40,
    distance: f.distance ?? 25,
    gender: f.gender ?? 'both',
    datingPurpose: f.dating_purpose ?? '',
    bodyType: f.body_type ?? '',
    eyeColor: f.eye_color ?? '',
    hairColor: f.hair_color ?? '',
    smoking: f.smoking ?? '',
    drinking: f.drinking ?? '',
    children: f.children ?? '',
    zodiacSign: f.zodiac_sign ?? '',
    exercise: f.exercise ?? '',
    minHeight: f.min_height ?? null,
    maxHeight: f.max_height ?? null,
    minWeight: f.min_weight ?? null,
    maxWeight: f.max_weight ?? null,
    interests: Array.isArray(f.interests) ? [...f.interests] : [],
  }

  setTimeout(() => { isReady.value = true }, 0)
}

const handlePointerUp = () => {
  if (isDraggingSlider.value) {
    isDraggingSlider.value = false
    if (isReady.value) debouncedSave()
  }
}

onMounted(async () => {
  document.addEventListener('pointerup', handlePointerUp)
  document.addEventListener('touchend', handlePointerUp)
  
  await subscriptionStore.fetchStatus()
  const result = await profileStore.fetchFilters()
  if (!result?.success) toast.error('Unable to load filters.')
  syncForm()
})

onUnmounted(() => {
  document.removeEventListener('pointerup', handlePointerUp)
  document.removeEventListener('touchend', handlePointerUp)
})

watch(() => profileStore.filters, syncForm, { immediate: true })

const debouncedSave = () => {
  clearTimeout(_saveTimer)
  _saveTimer = setTimeout(() => save(), 800)
}

watch(settings, () => {
  if (!isReady.value) return
  if (isDraggingSlider.value) return
  debouncedSave()
}, { deep: true })

const enableAdvanced = computed({
  get: () => profileStore.useAdditionalFilters,
  set: (val) => {
    if (!isPremium.value) return
    profileStore.setUseAdditionalFilters(val, { refresh: false })
    if (isReady.value) debouncedSave()
  },
})

const toNumber = (value) => {
  if (value === '' || value === null || value === undefined) return null
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

const setIfPresent = (payload, key, value) => {
  if (value === null || value === undefined) return
  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (trimmed) payload[key] = trimmed
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
  const minAge = Number(settings.value.minAge)
  const maxAge = Number(settings.value.maxAge)
  const distance = Number(settings.value.distance)

  if (!Number.isFinite(minAge) || !Number.isFinite(maxAge)) {
    toast.error('Please enter valid ages.')
    return
  }
  if (minAge >= maxAge) {
    toast.error('Min age must be lower than max age.')
    return
  }
  if (!distance || distance < 1) {
    toast.error('Distance must be at least 1 km.')
    return
  }
  if (isPremium.value && selectedInterests.value.length > 10) {
    toast.error('Select up to 10 interests.')
    return
  }

  const payload = {
    min_age: minAge,
    max_age: maxAge,
    distance,
    gender: settings.value.gender,
    use_advanced_filters: isPremium.value ? Boolean(enableAdvanced.value) : false,
  }

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

    payload.min_height = toNumber(settings.value.minHeight)
    payload.max_height = toNumber(settings.value.maxHeight)
    payload.min_weight = toNumber(settings.value.minWeight)
    payload.max_weight = toNumber(settings.value.maxWeight)

    if (selectedInterests.value.length > 0) {
      payload.interests = selectedInterests.value
    } else {
      payload.interests = []
    }
  }

  isSaving.value = true
  try {
    const result = await profileStore.updateFilters(payload)
    if (!result.success) {
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

        <section class="glass-panel" style="padding: 28px;">
          <div class="mb-8 flex flex-col gap-2 border-b border-slate-200/50 pb-5">
            <h2 class="text-2xl font-bold text-slate-950">Discovery Settings</h2>
            <p class="text-[0.9rem] text-slate-500">Set your basic preferences for who you want to see.</p>
          </div>

          <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="flex flex-col gap-6 md:col-span-2">
              <div 
                class="p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm"
                @pointerdown="isDraggingSlider = true" 
                @touchstart="isDraggingSlider = true"
              >
                <SingleRangeSlider
                  v-model="settings.distance"
                  :min="1"
                  :max="2000"
                  label="Maximum Distance"
                  suffix=" km"
                />
              </div>

              <div 
                class="p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm"
                @pointerdown="isDraggingSlider = true" 
                @touchstart="isDraggingSlider = true"
              >
                <DualRangeSlider
                  v-model:model-min="settings.minAge"
                  v-model:model-max="settings.maxAge"
                  :min="18"
                  :max="100"
                  label="Age Range"
                />
              </div>
            </div>

            <div class="flex flex-col gap-6 p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <div class="space-y-3">
                <label class="text-[0.95rem] font-bold text-slate-800">Show Me</label>
                <GlassDropdown
                  v-model="settings.gender"
                  :options="[
                    { value: 'both', label: 'Everyone' },
                    { value: 'woman', label: 'Women' },
                    { value: 'man', label: 'Men' },
                  ]"
                  placeholder="Everyone"
                  :showEmpty="false"
                />
              </div>
            </div>
          </div>
        </section>

        <section v-if="isPremium" class="glass-panel" style="padding: 28px;">
          <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            :class="{ 'mb-8 border-b border-slate-200/50 pb-5': enableAdvanced }"
          >
            <div class="flex flex-col">
              <h2 class="text-2xl font-bold text-slate-950">Premium Filters</h2>
              <p class="text-[0.9rem] text-slate-500 mt-1">
                Refine your search by lifestyle, appearance and interests
              </p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center shrink-0">
              <input type="checkbox" class="peer sr-only" v-model="enableAdvanced" />
              <div class="h-8 w-14 rounded-full border border-slate-300 bg-slate-200 transition duration-300 peer-checked:bg-gradient-to-r peer-checked:from-amber-400 peer-checked:to-yellow-500 peer-checked:border-transparent shadow-inner"></div>
              <div class="pointer-events-none absolute left-1 top-1 h-6 w-6 rounded-full bg-white shadow-sm transition-all duration-300 peer-checked:translate-x-6"></div>
            </label>
          </div>

          <div
            v-if="enableAdvanced"
            class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 animate-fade-in-up"
            style="align-items: stretch;"
          >
            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <h3 class="mb-5 text-[1.1rem] font-bold text-slate-900">Lifestyle</h3>
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

            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm">
              <h3 class="mb-5 text-[1.1rem] font-bold text-slate-900">Appearance</h3>
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
                
                <div 
                  class="pt-3 border-t border-slate-200/50 mt-1"
                  @pointerdown="isDraggingSlider = true" 
                  @touchstart="isDraggingSlider = true"
                >
                  <DualRangeSlider
                    v-model:model-min="settings.minHeight"
                    v-model:model-max="settings.maxHeight"
                    :min="70"
                    :max="250"
                    label="Height"
                    suffix=" cm"
                    :nullable="true"
                    not-set-label="Not set"
                  />
                  <div class="flex justify-end gap-3 text-[0.72rem] mt-1 px-1">
                    <button 
                      v-if="settings.minHeight !== null" 
                      type="button" 
                      @click.stop="settings.minHeight = null" 
                      class="text-slate-400 hover:text-rose-500 transition font-medium"
                    >
                      × Clear Min
                    </button>
                    <button 
                      v-if="settings.maxHeight !== null" 
                      type="button" 
                      @click.stop="settings.maxHeight = null" 
                      class="text-slate-400 hover:text-rose-500 transition font-medium"
                    >
                      × Clear Max
                    </button>
                  </div>
                </div>
                
                <div 
                  class="pt-1"
                  @pointerdown="isDraggingSlider = true" 
                  @touchstart="isDraggingSlider = true"
                >
                  <DualRangeSlider
                    v-model:model-min="settings.minWeight"
                    v-model:model-max="settings.maxWeight"
                    :min="20"
                    :max="200"
                    label="Weight"
                    suffix=" kg"
                    :nullable="true"
                    not-set-label="Not set"
                  />
                  <div class="flex justify-end gap-3 text-[0.72rem] mt-1 px-1">
                    <button 
                      v-if="settings.minWeight !== null" 
                      type="button" 
                      @click.stop="settings.minWeight = null" 
                      class="text-slate-400 hover:text-rose-500 transition font-medium"
                    >
                      × Clear Min
                    </button>
                    <button 
                      v-if="settings.maxWeight !== null" 
                      type="button" 
                      @click.stop="settings.maxWeight = null" 
                      class="text-slate-400 hover:text-rose-500 transition font-medium"
                    >
                      × Clear Max
                    </button>
                  </div>
                </div>
              </div>
            </section>

            <section class="flex flex-col p-6 rounded-[24px] bg-white/50 border border-slate-200/60 shadow-sm md:col-span-2 lg:col-span-1">
              <div class="mb-5 flex items-center justify-between">
                <h3 class="text-[1.1rem] font-bold text-slate-900">Interests</h3>
                <span class="text-[0.75rem] font-bold px-2.5 py-1 bg-white border border-slate-200 rounded-full text-slate-600 shadow-sm">
                  {{ selectedInterests.length }}/10
                </span>
              </div>

              <div class="mb-5 flex flex-wrap gap-2">
                <template v-if="selectedInterests.length">
                  <button
                    v-for="interest in selectedInterests"
                    :key="interest"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-gradient-to-r from-amber-50 to-yellow-50 px-3 py-1.5 text-[0.85rem] font-semibold text-amber-800 transition hover:from-amber-100 hover:to-yellow-100 shadow-sm"
                    @click="removeInterest(interest)"
                  >
                    <span>{{ interestOptions.find((opt) => opt.value === interest)?.label ?? interest }}</span>
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-amber-200/80 text-[10px] font-bold text-amber-900">×</span>
                  </button>
                </template>
                <p v-else class="text-[0.85rem] text-slate-500 italic">No interests selected.</p>
              </div>

              <div class="relative mb-4">
                <svg
                  width="15" height="15" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2.2"
                  stroke-linecap="round" stroke-linejoin="round"
                  class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                >
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input
                  v-model="interestQuery"
                  type="text"
                  placeholder="Search interests..."
                  class="w-full rounded-xl border border-slate-300/80 bg-white/70 py-2.5 pl-9 pr-3 text-[0.85rem] text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-[3px] focus:ring-amber-400/10"
                />
              </div>

              <div class="flex flex-col gap-1.5 overflow-y-auto pr-1 custom-scrollbar" style="max-height: 280px;">
                <button
                  v-for="opt in filteredInterestOptions"
                  :key="opt.value"
                  type="button"
                  class="rounded-xl border border-transparent bg-white/60 px-4 py-2.5 text-left text-[0.85rem] font-medium text-slate-700 transition hover:border-amber-200 hover:bg-white hover:shadow-sm"
                  @click="addInterest(opt.value)"
                >
                  {{ opt.label }}
                </button>
                <p v-if="!filteredInterestOptions.length" class="p-2 text-[0.85rem] text-slate-500 text-center">
                  No matching interests.
                </p>
              </div>
            </section>
          </div>
        </section>

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
              <button
                @click="goPremium"
                :disabled="isSubscriptionLoading"
                class="relative flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 py-3.5 px-6 text-[1rem] font-bold text-white shadow-lg transition hover:from-slate-800 hover:to-slate-700 hover:shadow-xl active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed"
              >
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