<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProfileStore } from '@/stores/profile.js'
import { useSubscriptionStore } from '@/stores/subscription.js'
import { useToast } from '@/composables/useToast.js'
import { SELECT_OPTIONS, INTEREST_OPTIONS, formatLabel } from '@/constants/profileOptions.js'
import BaseInput from '@/components/BaseInput.vue'
import BaseButton from '@/components/BaseButton.vue'
import GlassDropdown from '@/components/GlassDropdown.vue'
import SingleRangeSlider from '@/components/SingleRangeSlider.vue'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useProfileStore()
const subscriptionStore = useSubscriptionStore()
const { toast } = useToast()

const form = reactive({
  name: '',
  dateOfBirth: '',
  gender: '',
  description: '',
  datingPurpose: '',
  height: '',
  weight: '',
  bodyType: '',
  eyeColor: '',
  hairColor: '',
  smoking: '',
  drinking: '',
  children: '',
  zodiacSign: '',
  exercise: '',
})

const isSaving = ref(false)
const isUploading = ref(false)
const enableError = ref('')
const missingFields = ref([])
const interestQuery = ref('')
const isUpdatingInterests = ref(false)
const selectOptions = SELECT_OPTIONS
const interestOptions = INTEREST_OPTIONS
const maxInterests = 10
const isPremium = computed(() => subscriptionStore.isPremium)
const isSubscriptionLoading = computed(() => subscriptionStore.isLoading)
const subscriptionError = computed(() => subscriptionStore.error)
const isCanceled = computed(() => subscriptionStore.isCanceled)
const endsAt = computed(() => subscriptionStore.endsAt)

const bDay = ref('')
const bMonth = ref('')
const bYear = ref('')

watch([bYear, bMonth, bDay], ([y, m, d]) => {
  if (y && m && d) {
    form.dateOfBirth = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`
  } else {
    form.dateOfBirth = ''
  }
})

const daysOptions = Array.from({ length: 31 }, (_, i) => ({ value: String(i + 1).padStart(2, '0'), label: String(i + 1) }))
const monthsOptions = [
  { value: '01', label: 'January' }, { value: '02', label: 'February' }, { value: '03', label: 'March' },
  { value: '04', label: 'April' }, { value: '05', label: 'May' }, { value: '06', label: 'June' },
  { value: '07', label: 'July' }, { value: '08', label: 'August' }, { value: '09', label: 'September' },
  { value: '10', label: 'October' }, { value: '11', label: 'November' }, { value: '12', label: 'December' }
]
const currentYear = new Date().getFullYear()
const yearsOptions = Array.from({ length: 100 }, (_, i) => {
  const y = String(currentYear - 18 - i)
  return { value: y, label: y }
})

const fieldLabels = {
  name: 'Name',
  date_of_birth: 'Birth date',
  gender: 'Gender',
  city: 'City',
  photos: 'Photo',
}

const myProfile = computed(() => profileStore.myProfile)
const isLoading = computed(() => profileStore.isLoadingProfile)
const completionPct = computed(() => profileStore.completionPct)
const isEnabled = computed(() => myProfile.value?.is_enabled)
const photos = computed(() => myProfile.value?.photos || [])
const profileInterests = computed(() =>
  Array.isArray(myProfile.value?.interests) ? myProfile.value.interests : []
)
const currentInterestValues = computed(() =>
  profileInterests.value.map((interest) => interest.interest)
)
const availableInterestOptions = computed(() =>
  interestOptions.filter((opt) => !currentInterestValues.value.includes(opt.value))
)
const filteredInterestOptions = computed(() => {
  const query = interestQuery.value.trim().toLowerCase()
  const source = availableInterestOptions.value
  if (!query) return source
  return source
    .filter((opt) => opt.label.toLowerCase().includes(query) || opt.value.includes(query))
})
const remainingInterestSlots = computed(() =>
  Math.max(0, maxInterests - profileInterests.value.length)
)

const interestLabel = (value) =>
  interestOptions.find((opt) => opt.value === value)?.label || formatLabel(value)

const locationLabel = computed(() => {
  const city = myProfile.value?.city
  const country = myProfile.value?.country
  return [city, country].filter(Boolean).join(', ')
})

const previewPhoto = computed(() => photos.value[0]?.url || null)
const previewName = computed(() => myProfile.value?.name || 'Your Name')
const previewAge = computed(() => myProfile.value?.age || null)
const previewLocation = computed(() => locationLabel.value || 'Add your city')
const previewBio = computed(() => myProfile.value?.description || 'Add a short bio to make your profile stand out.')
const previewGender = computed(() => {
  const g = myProfile.value?.gender
  if (g === 'woman') return 'Woman'
  if (g === 'man') return 'Man'
  return null
})

const missingFieldLabels = computed(() =>
  missingFields.value.map((f) => fieldLabels[f] || f)
)

watch(myProfile, (profile) => {
  if (!profile) return
  form.name = profile.name || ''
  
  const dob = profile.date_of_birth || ''
  form.dateOfBirth = dob
  if (dob) {
    const [y, m, d] = dob.split('-')
    bYear.value = y
    bMonth.value = m
    bDay.value = d
  } else {
    bYear.value = ''
    bMonth.value = ''
    bDay.value = ''
  }

  form.gender = profile.gender || ''
  form.description = profile.description || ''
  form.datingPurpose = profile.dating_purpose || ''
  form.height = profile.height ?? ''
  form.weight = profile.weight ?? ''
  form.bodyType = profile.body_type || ''
  form.eyeColor = profile.eye_color || ''
  form.hairColor = profile.hair_color || ''
  form.smoking = profile.smoking || ''
  form.drinking = profile.drinking || ''
  form.children = profile.children || ''
  form.zodiacSign = profile.zodiac_sign || ''
  form.exercise = profile.exercise || ''
}, { immediate: true })

onMounted(async () => {
  await profileStore.fetchMyProfile()
  await subscriptionStore.fetchStatus()
})

const saveProfile = async () => {
  enableError.value = ''
  missingFields.value = []
  isSaving.value = true

  const payload = {}
  const setIfPresent = (key, value) => {
    if (value === '' || value === null || value === undefined) return
    if (typeof value === 'string') {
      const normalized = value.trim()
      if (!normalized) return
      payload[key] = normalized
      return
    }
    payload[key] = value
  }

  const numberIfValid = (value) => {
    if (value === '' || value === null || value === undefined) return null
    const parsed = Number(value)
    return Number.isFinite(parsed) ? parsed : null
  }

  setIfPresent('name', form.name)
  setIfPresent('date_of_birth', form.dateOfBirth)
  setIfPresent('gender', form.gender)
  setIfPresent('description', form.description)
  setIfPresent('dating_purpose', form.datingPurpose)
  setIfPresent('body_type', form.bodyType)
  setIfPresent('eye_color', form.eyeColor)
  setIfPresent('hair_color', form.hairColor)
  setIfPresent('smoking', form.smoking)
  setIfPresent('drinking', form.drinking)
  setIfPresent('children', form.children)
  setIfPresent('zodiac_sign', form.zodiacSign)
  setIfPresent('exercise', form.exercise)

  const height = numberIfValid(form.height)
  const weight = numberIfValid(form.weight)
  if (height !== null) payload.height = height
  if (weight !== null) payload.weight = weight

  const result = await profileStore.updateMyProfile(payload)

  isSaving.value = false

  if (result.success) {
    toast.success('Profile updated.')
  } else {
    toast.error(result.message || 'Unable to save profile.')
  }
}

const toggleVisibility = async () => {
  enableError.value = ''
  missingFields.value = []

  const result = await profileStore.toggleProfileVisibility(!isEnabled.value)
  if (result.success) {
    toast.success(isEnabled.value ? 'Profile disabled.' : 'Profile enabled.')
  } else {
    enableError.value = result.message || 'Unable to update visibility.'
    const missing = result.missingFields || []
    if (!photos.value.length && !missing.includes('photos')) {
      missing.push('photos')
    }
    missingFields.value = missing
  }
}

const handleUpload = async (event) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return
  isUploading.value = true
  const result = await profileStore.uploadPhotos(files)
  isUploading.value = false
  event.target.value = ''

  if (result.success) {
    toast.success('Photos uploaded. Validation in progress.')
  } else {
    toast.error(result.message || 'Unable to upload photos.')
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'home' })
}

const deletePhoto = async (photoId) => {
  const result = await profileStore.deletePhoto(photoId)
  if (result.success) {
    toast.success('Photo deleted.')
  } else {
    toast.error('Unable to delete photo.')
  }
}

const addInterest = async (value) => {
  if (!value || isUpdatingInterests.value) return
  if (remainingInterestSlots.value <= 0) {
    toast.error('You can select up to 10 interests.')
    return
  }

  isUpdatingInterests.value = true
  const result = await profileStore.addInterest(value)
  isUpdatingInterests.value = false

  if (result.success) {
    toast.success('Interest added.')
    interestQuery.value = ''
  } else {
    toast.error(result.message || 'Unable to update interests.')
  }
}

const removeInterest = async (interestId) => {
  if (!interestId || isUpdatingInterests.value) return
  isUpdatingInterests.value = true

  const result = await profileStore.deleteInterest(interestId)
  isUpdatingInterests.value = false

  if (result.success) {
    toast.success('Interest removed.')
  } else {
    toast.error(result.message || 'Unable to remove interest.')
  }
}

const goPremium = async () => {
  await subscriptionStore.startCheckout()
}

const cancelPremium = async () => {
  const result = await subscriptionStore.cancel()
  if (result.success) {
    toast.success('Subscription cancelled.')
  } else {
    toast.error(subscriptionError.value || 'Unable to cancel subscription.')
  }
}

const resumePremium = async () => {
  const result = await subscriptionStore.resume()
  if (result.success) {
    toast.success('Subscription resumed.')
  } else {
    toast.error(subscriptionStore.error || 'Unable to resume subscription.')
  }
}
</script>

<template>
  <div class="page px-4 py-6 sm:px-6 lg:px-8">
    <div class="page-header max-w-6xl mx-auto mb-2"></div>

    <div v-if="isLoading && !myProfile" class="glass-panel p-5 text-sm text-slate-500">
      Loading profile...
    </div>

    <div v-else class="grid gap-[18px] lg:grid-cols-[1.15fr_0.85fr] max-w-6xl mx-auto lg:items-start">
      <div class="glass-panel p-6 flex flex-col">
        <form id="profile-form" @submit.prevent="saveProfile" class="flex flex-col gap-4">
          <BaseInput v-model="form.name" type="text" label="Display Name" placeholder="Your name" required />

          <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-[5px]">
              <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Birth Date</label>
              <div class="grid gap-2" style="grid-template-columns: 2fr 3fr 2fr;">
                <GlassDropdown v-model="bDay" :options="daysOptions" placeholder="Day" empty-label="Day" center />
                <GlassDropdown v-model="bMonth" :options="monthsOptions" placeholder="Month" empty-label="Month" center />
                <GlassDropdown v-model="bYear" :options="yearsOptions" placeholder="Year" empty-label="Year" center />
              </div>
            </div>
            <div class="flex flex-col gap-[5px]">
              <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]" for="gender">Gender</label>
              <GlassDropdown
                v-model="form.gender"
                :options="[{value:'woman', label:'Woman'}, {value:'man', label:'Man'}]"
                placeholder="Select"
                empty-label="Select"
              />
            </div>
          </div>

          <div class="flex flex-col gap-[5px]">
            <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">About Me</label>
            <div class="field__wrap">
              <textarea
                v-model="form.description"
                class="w-full h-[200px] min-h-[200px] max-h-[200px] resize-none leading-[1.6] bg-white/65 backdrop-blur-md border border-slate-300/60 rounded-[14px] px-4 py-3.5 text-[0.9rem] text-slate-800 outline-none transition-[border-color,box-shadow,background-color] duration-[180ms] ease-out focus:border-cyan-300 focus:ring-[3px] focus:ring-cyan-400/15 focus:bg-white/90"
                placeholder="Tell them about yourself..."
                maxlength="1200"
              ></textarea>
            </div>
            <span class="text-[0.75rem] font-medium text-slate-400 text-right">{{ (form.description || '').length }} / 1200</span>
          </div>

          <div class="flex flex-col gap-3 py-1.5">
            <p class="m-0 text-[0.75rem] font-bold uppercase tracking-[0.08em] text-slate-500">More about you</p>
            <div class="flex flex-col gap-4">
              <div class="grid gap-6 sm:grid-cols-2">
                <SingleRangeSlider v-model="form.height" :min="130" :max="250" :fallback="170" label="Height" suffix=" cm" />
                <SingleRangeSlider v-model="form.weight" :min="40" :max="150" :fallback="70" label="Weight" suffix=" kg" />
              </div>

              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Dating Purpose</label>
                  <GlassDropdown v-model="form.datingPurpose" :options="selectOptions.datingPurpose" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Body Type</label>
                  <GlassDropdown v-model="form.bodyType" :options="selectOptions.bodyType" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Eye Color</label>
                  <GlassDropdown v-model="form.eyeColor" :options="selectOptions.eyeColor" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Hair Color</label>
                  <GlassDropdown v-model="form.hairColor" :options="selectOptions.hairColor" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Smoking</label>
                  <GlassDropdown v-model="form.smoking" :options="selectOptions.smoking" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Drinking</label>
                  <GlassDropdown v-model="form.drinking" :options="selectOptions.drinking" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Children</label>
                  <GlassDropdown v-model="form.children" :options="selectOptions.children" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Zodiac Sign</label>
                  <GlassDropdown v-model="form.zodiacSign" :options="selectOptions.zodiacSign" placeholder="Empty" />
                </div>
                <div class="flex flex-col gap-[5px]">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Exercise</label>
                  <GlassDropdown v-model="form.exercise" :options="selectOptions.exercise" placeholder="Empty" />
                </div>
              </div>
            </div>
          </div>
        </form>

        <div class="mt-4 flex flex-col gap-2.5 rounded-2xl border border-slate-200/60 bg-white/45 p-4">
          <div class="flex items-center justify-between">
            <p class="m-0 text-[0.78rem] font-bold uppercase tracking-[0.08em] text-slate-500">Interests</p>
            <p class="m-0 text-[0.78rem] text-slate-500">{{ profileInterests.length }}/{{ maxInterests }} selected</p>
          </div>

          <div v-if="!profileInterests.length" class="text-[0.85rem] text-slate-500">
            Add a few interests to help with better matches.
          </div>

          <div v-else class="flex flex-col gap-2">
            <div class="flex items-center gap-3">
              <span class="text-[0.72rem] font-bold uppercase tracking-[0.08em] text-pink-400">Selected</span>
              <div class="flex-1 h-px bg-pink-200/70"></div>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2">
              <button
                v-for="interest in profileInterests"
                :key="interest.id"
                type="button"
                class="flex items-center justify-between gap-2 rounded-xl border border-pink-500/60 bg-pink-500/15 px-2.5 py-2 text-[0.8rem] font-semibold text-pink-600 shadow-sm transition hover:border-pink-500/80 hover:bg-pink-500/20 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isUpdatingInterests"
                @click="removeInterest(interest.id)"
              >
                <span>{{ interestLabel(interest.interest) }}</span>
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-pink-500/25 text-[0.9rem] font-bold text-pink-600">×</span>
              </button>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-1">
            <span class="text-[0.72rem] font-bold uppercase tracking-[0.08em] text-slate-400">Add interests</span>
            <div class="flex-1 h-px bg-slate-200/80"></div>
          </div>

          <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2 overflow-y-auto content-start pr-1 max-h-[320px]">
            <button
              v-for="opt in filteredInterestOptions"
              :key="opt.value"
              type="button"
              class="flex items-center justify-between gap-2 rounded-xl border border-slate-200/75 bg-[#f0f9ff] px-2.5 py-2 text-[0.8rem] font-semibold text-slate-900 transition hover:border-cyan-500 hover:bg-cyan-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="remainingInterestSlots === 0 || isUpdatingInterests"
              @click="addInterest(opt.value)"
            >
              <span>{{ opt.label }}</span>
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-cyan-50 text-[0.9rem] font-bold text-cyan-500">+</span>
            </button>
          </div>
          <p v-if="!filteredInterestOptions.length" class="m-0 text-[0.75rem] text-slate-500">No matching interests.</p>
          <p class="m-0 text-[0.75rem] text-slate-500">
            Choose up to {{ remainingInterestSlots }} more interest{{ remainingInterestSlots === 1 ? '' : 's' }}.
          </p>
        </div>
      </div>

      <div class="flex flex-col gap-6">
        <div class="rounded-3xl border border-white/60 bg-white/50 p-5 shadow-sm backdrop-blur-xl">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-700">Profile Completeness</span>
            <span class="text-sm font-black text-cyan-600">{{ completionPct }}%</span>
          </div>
          <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80 shadow-inner">
            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-cyan-500 transition-all duration-700" :style="{ width: `${completionPct}%` }"></div>
          </div>
        </div>

        <div class="glass-panel flex flex-col gap-4 p-6">
          <div class="relative overflow-hidden rounded-3xl bg-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-white/60">
            <div
              class="absolute right-3 top-3 z-10 rounded-full px-3 py-1 text-[10px] font-bold text-white shadow-sm backdrop-blur-md uppercase tracking-wider"
              :class="isEnabled ? 'bg-emerald-500/80' : 'bg-slate-800/60'"
            >
              {{ isEnabled ? 'Visible in discovery' : 'Hidden' }}
            </div>
            <div class="h-[360px] w-full bg-slate-100">
              <img v-if="previewPhoto" :src="previewPhoto" alt="Profile preview" class="h-full w-full object-contain" />
              <div v-else class="flex h-full w-full items-center justify-center text-4xl font-black text-cyan-200">
                No Photo
              </div>
            </div>
            <div class="border-t border-white/70 bg-white/90 p-4">
              <div class="flex items-baseline gap-2">
                <h2 class="text-xl font-bold text-slate-900">{{ previewName }}</h2>
                <span v-if="previewAge" class="text-base font-semibold text-slate-700">{{ previewAge }}</span>
              </div>
              <div class="mt-1 flex items-center gap-2 text-sm font-medium text-slate-600">
                <span v-if="previewLocation" class="flex items-center gap-1">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ previewLocation }}
                </span>
                <span v-if="previewGender">· {{ previewGender }}</span>
              </div>
              <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ previewBio }}</p>
            </div>
          </div>

          <div class="divider-glow" />

          <div class="flex items-center justify-between">
            <p class="m-0 text-[0.95rem] font-semibold text-slate-900">Photos</p>
            <label class="inline-flex cursor-pointer items-center justify-center gap-1 rounded-full border border-slate-200/75 bg-[#f0f9ff] px-3.5 py-1.5 text-[0.8rem] font-semibold text-slate-900 transition hover:border-cyan-500 hover:bg-cyan-50">
              <input type="file" class="sr-only" multiple accept="image/*" @change="handleUpload" />
              {{ isUploading ? 'Uploading...' : 'Add Photos' }}
            </label>
          </div>

          <div v-if="photos.length" class="grid grid-cols-2 gap-4">
            <div v-for="photo in photos" :key="photo.id" class="relative overflow-hidden rounded-2xl border border-slate-200/75 bg-white/80 shadow-sm">
              <img :src="photo.url" alt="Profile photo" class="block h-[180px] w-full object-cover sm:h-[200px] lg:h-[220px]" />
              <button type="button" class="absolute right-1.5 top-1.5 rounded-full border-none bg-black/60 px-2 py-0.5 text-[0.7rem] text-white cursor-pointer" @click="deletePhoto(photo.id)">
                Delete
              </button>
            </div>
          </div>
          <div v-else class="text-[0.85rem] text-slate-500">
            Add at least one photo to enable discovery.
          </div>

          <div class="divider-glow" />

          <div class="flex flex-col gap-2.5 rounded-2xl border border-dashed border-cyan-500/45 bg-gradient-to-br from-cyan-500/10 to-green-500/5 p-3.5">
            <div class="flex items-start justify-between gap-2.5">
              <div>
                <p class="m-0 mb-1 text-[0.7rem] font-bold uppercase tracking-[0.12em] text-slate-500">Premium</p>
                <h3 class="m-0 text-base font-bold text-slate-900">Upgrade your discovery</h3>
              </div>
              <span class="chip">{{ isCanceled ? 'Cancelled' : isPremium ? 'Active' : 'Locked' }}</span>
            </div>
            <p class="m-0 text-[0.85rem] text-slate-700">
  Get advanced filters, profile boost in discovery, swipe rollback, and deeper matches with Premium membership.
</p>

            <template v-if="isCanceled">
              <p class="m-0 text-[0.85rem] text-slate-500">
                Premium active until {{ endsAt ?? '...' }}
              </p>
                <BaseButton variant="primary" full :loading="isSubscriptionLoading" @click="resumePremium">
                    Resume Premium
  </BaseButton>
            </template>
            <template v-else-if="isPremium">
              <BaseButton variant="secondary" full :loading="isSubscriptionLoading" @click="cancelPremium">
                Cancel Premium
              </BaseButton>
            </template>
            <template v-else>
              <BaseButton variant="primary" full :loading="isSubscriptionLoading" @click="goPremium">
                Get Premium
              </BaseButton>
            </template>

            <p v-if="subscriptionError" class="m-0 text-[0.78rem] text-rose-500">{{ subscriptionError }}</p>
          </div>

          <div class="divider-glow" />

          <div class="flex flex-col gap-2">
            <BaseButton variant="outline" full @click="toggleVisibility">
              {{ isEnabled ? 'Disable Profile' : 'Enable Profile' }}
            </BaseButton>
            <p v-if="enableError" class="m-0 text-[0.85rem] text-rose-500">{{ enableError }}</p>
            <p v-if="missingFieldLabels.length" class="m-0 text-[0.78rem] text-slate-500">
              Missing fields: {{ missingFieldLabels.join(', ') }}
            </p>
          </div>

          <div class="divider-glow" />

          <div class="glass-panel p-4">
            <BaseButton variant="danger-outline" full @click="handleLogout">
              Log Out
            </BaseButton>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 max-w-6xl mx-auto">
      <BaseButton form="profile-form" type="submit" variant="primary" full :loading="isSaving">
        Save Changes
      </BaseButton>
    </div>
  </div>
</template>