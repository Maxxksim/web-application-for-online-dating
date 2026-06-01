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
    profileStore.setUseAdditionalFilters(val)
  }
})

const isAdvancedLocked = computed(() => !isPremium.value || !enableAdvanced.value)

watch(isPremium, (value) => {
  if (!value) {
    profileStore.setUseAdditionalFilters(false, { refresh: false })
  }
}, { immediate: true })

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


      <div :class="isPremium ? 'grid gap-6 lg:grid-cols-[1.2fr_0.8fr]' : 'flex flex-col gap-6'">
        <section class="glass-panel" style="padding: 24px;">
          <div class="mb-6 flex flex-col gap-3">
            <h2 class="text-2xl font-semibold text-slate-950">Basic filters</h2>
          </div>
          <div class="space-y-6">
            <div class="p-[18px] rounded-2xl bg-white/45 border border-slate-200/60">
              <SingleRangeSlider
                v-model="settings.distance"
                :min="1"
                :max="200"
                label="Maximum Distance"
                suffix=" km"
              />
            </div>

            <DualRangeSlider
              v-model:model-min="settings.minAge"
              v-model:model-max="settings.maxAge"
              :min="18"
              :max="80"
              label="Age"
            />

            <div class="space-y-2">
              <label for="search-gender" class="text-sm font-semibold text-slate-700">Gender</label>
              <GlassDropdown
                v-model="settings.gender"
                :options="[{value:'both', label:'Everyone'}, {value:'woman', label:'Women'}, {value:'man', label:'Men'}]"
                placeholder="Everyone"
                :showEmpty="false"
              />
            </div>

            <div class="pt-4 mt-4">
              <BaseButton @click="save" variant="primary" full :loading="isSaving" :disabled="!showFiltersContent || isSaving">
                Apply filters
              </BaseButton>
            </div>
          </div>
        </section>

        <section v-if="isPremium" class="glass-panel" style="padding: 24px;">
          <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-2xl font-semibold text-slate-950">Premium filters</h2>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-slate-600">Enabled</span>
              <label class="relative inline-flex cursor-pointer items-center">
                <input
                  type="checkbox"
                  class="peer sr-only"
                  v-model="enableAdvanced"
                />
                <div class="h-6 w-11 rounded-full border border-slate-300 bg-slate-200 transition duration-200 peer-checked:bg-cyan-600"></div>
                <div class="pointer-events-none absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow transition duration-200 peer-checked:translate-x-5"></div>
              </label>
            </div>
          </div>

          <div class="space-y-6">
            <fieldset :disabled="isAdvancedLocked" class="space-y-6">
              <div class="grid gap-6 lg:grid-cols-2">
                <section class="p-[18px] rounded-2xl bg-white/45 border border-slate-200/60">
                  <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Lifestyle</h3>
                    <span class="text-sm text-slate-500">Optional</span>
                  </div>
                  <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                      <label for="dating-purpose" class="text-sm font-semibold text-slate-700">Dating purpose</label>
                      <GlassDropdown v-model="settings.datingPurpose" :options="selectOptions.datingPurpose" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="smoking" class="text-sm font-semibold text-slate-700">Smoking</label>
                      <GlassDropdown v-model="settings.smoking" :options="selectOptions.smoking" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="drinking" class="text-sm font-semibold text-slate-700">Drinking</label>
                      <GlassDropdown v-model="settings.drinking" :options="selectOptions.drinking" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="exercise" class="text-sm font-semibold text-slate-700">Exercise</label>
                      <GlassDropdown v-model="settings.exercise" :options="selectOptions.exercise" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="zodiac-sign" class="text-sm font-semibold text-slate-700">Zodiac sign</label>
                      <GlassDropdown v-model="settings.zodiacSign" :options="selectOptions.zodiacSign" placeholder="Any" empty-label="Any" />
                    </div>
                  </div>
                </section>

                <section class="p-[18px] rounded-2xl bg-white/45 border border-slate-200/60">
                  <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Appearance</h3>
                    <span class="text-sm text-slate-500">Optional</span>
                  </div>
                  <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                      <label for="body-type" class="text-sm font-semibold text-slate-700">Body type</label>
                      <GlassDropdown v-model="settings.bodyType" :options="selectOptions.bodyType" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="eye-color" class="text-sm font-semibold text-slate-700">Eye color</label>
                      <GlassDropdown v-model="settings.eyeColor" :options="selectOptions.eyeColor" placeholder="Any" empty-label="Any" />
                    </div>
                    <div class="space-y-2">
                      <label for="hair-color" class="text-sm font-semibold text-slate-700">Hair color</label>
                      <GlassDropdown v-model="settings.hairColor" :options="selectOptions.hairColor" placeholder="Any" empty-label="Any" />
                    </div>
                  </div>

                  <DualRangeSlider
                    v-model:model-min="settings.minHeight"
                    v-model:model-max="settings.maxHeight"
                    :min="130"
                    :max="250"
                    label="Height"
                    suffix=" cm"
                  />
                  <DualRangeSlider
                    v-model:model-min="settings.minWeight"
                    v-model:model-max="settings.maxWeight"
                    :min="40"
                    :max="150"
                    label="Weight"
                    suffix=" kg"
                  />
                </section>
              </div>

              <section class="p-[18px] rounded-2xl bg-white/45 border border-slate-200/60">
                <div class="mb-4 flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-slate-900">Interests</h3>
                  <span class="text-sm text-slate-500">{{ selectedInterests.length }}/10</span>
                </div>
                <div class="mb-4 flex flex-wrap gap-2">
                  <template v-if="selectedInterests.length">
                    <button
                      v-for="interest in selectedInterests"
                      :key="interest"
                      type="button"
                      class="inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100"
                      @click="removeInterest(interest)"
                    >
                      <span>{{ interestOptions.find((opt) => opt.value === interest)?.label || interest }}</span>
                      <span class="rounded-full bg-cyan-200 px-2 py-0.5 text-[10px] font-bold">×</span>
                    </button>
                  </template>
                  <p v-else class="text-sm text-slate-500">No interests selected.</p>
                </div>

                <BaseInput
                  v-model="interestQuery"
                  type="text"
                  placeholder="Search interests..."
                  class="mb-3"
                />

                <div class="grid max-h-80 gap-2 overflow-auto pb-1">
                  <button
                    v-for="opt in filteredInterestOptions"
                    :key="opt.value"
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-800 transition hover:border-cyan-300 hover:bg-cyan-50"
                    @click="addInterest(opt.value)"
                  >
                    {{ opt.label }}
                  </button>
                </div>
                <p v-if="!filteredInterestOptions.length" class="mt-3 text-sm text-slate-500">No matching interests.</p>
              </section>
            </fieldset>

            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-600">
              <p v-if="!enableAdvanced">Enable advanced filters to start filtering by lifestyle, appearance, and interests.</p>
              <p v-else>Advanced filters are active. Continue refining your search.</p>
              <div class="mt-4">
                <BaseButton
                  variant="secondary"
                  full
                  :loading="isSubscriptionLoading"
                  @click="enableAdvanced = true"
                >
                  {{ enableAdvanced ? 'Advanced filters enabled' : 'Enable advanced filters' }}
                </BaseButton>
              </div>
            </div>
          </div>
        </section>

        <section v-else class="glass-panel" style="padding: 24px;">
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-950">Premium filters</h2>
            <p class="mt-2 text-sm text-slate-600">Unlock lifestyle, appearance, and interests filters with Premium.</p>
          </div>
          <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-600">
            <p>Premium membership is required to use advanced filters.</p>
            <div class="mt-4">
              <BaseButton
                variant="primary"
                full
                :loading="isSubscriptionLoading"
                @click="goPremium"
              >
                Get Premium
              </BaseButton>
            </div>
          </div>
        </section>
      </div>
    </div>


  </div>
</template>

