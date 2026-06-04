<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch, nextTick } from 'vue'
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
import { useGeolocation } from '@/composables/useGeolocation.js'
import { initReverb } from '@/realtime/reverb.js'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useProfileStore()
const subscriptionStore = useSubscriptionStore()
const { toast } = useToast()
const { syncLocation, isLocating, locationError: geoError } = useGeolocation()

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

const editing = reactive({
  name: false,
  description: false,
})

const originalValues = reactive({
  name: '',
  description: '',
})

const nameInputRef = ref(null)
const descriptionInputRef = ref(null)

const startEdit = async (field) => {
  originalValues[field] = (form[field] || '').trim()

  editing[field] = true
  await nextTick()
  if (field === 'name' && nameInputRef.value) {
    const el = nameInputRef.value.$el?.querySelector('input') || nameInputRef.value
    el?.focus()
  }
  if (field === 'description' && descriptionInputRef.value) {
    descriptionInputRef.value.focus()
  }
}

const stopEdit = async (field) => {
  if (!editing[field]) return

  editing[field] = false
  clearTimeout(_saveTimer)

  const currentValue = (form[field] || '').trim()

  if (currentValue === originalValues[field]) {
    form[field] = currentValue
    return
  }

  form[field] = currentValue
  await saveProfile()
}

const handleEnterKey = (event, field) => {
  event.target.blur()
}

const handleSliderChange = () => {
  if (!isReady.value) return
  debouncedSave()
}

const isSaving = ref(false)
const isUploading = ref(false)
const isReady = ref(false)

let _saveTimer = null
const debouncedSave = () => {
  clearTimeout(_saveTimer)
  _saveTimer = setTimeout(() => saveProfile(), 600)
}
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

const daysInSelectedMonth = computed(() => {
  const y = parseInt(bYear.value)
  const m = parseInt(bMonth.value)
  if (m && y) return new Date(y, m, 0).getDate()
  if (m) return new Date(2000, m, 0).getDate()
  return 31
})

const daysOptions = computed(() =>
  Array.from({ length: daysInSelectedMonth.value }, (_, i) => {
    const v = String(i + 1).padStart(2, '0')
    return { value: v, label: String(i + 1) }
  })
)

watch(daysInSelectedMonth, (max) => {
  if (bDay.value && parseInt(bDay.value) > max) {
    bDay.value = ''
  }
})

watch([bYear, bMonth, bDay], ([y, m, d]) => {
  if (y && m && d) {
    form.dateOfBirth = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`
  } else {
    form.dateOfBirth = ''
  }
})

const monthsOptions = [
  { value: '01', label: 'January' }, { value: '02', label: 'February' }, { value: '03', label: 'March' },
  { value: '04', label: 'April' }, { value: '05', label: 'June' }, { value: '06', label: 'July' },
  { value: '07', label: 'August' }, { value: '08', label: 'September' }, { value: '10', label: 'October' }, 
  { value: '11', label: 'November' }, { value: '12', label: 'December' }
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

const profileLoaded = ref(false)

watch(myProfile, (profile) => {
  if (!profile || profileLoaded.value) return
  profileLoaded.value = true

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
  form.datingPurpose = profile.datingPurpose || ''
  form.height = profile.height === 0 ? '' : (profile.height ?? '')
  form.weight = profile.weight === 0 ? '' : (profile.weight ?? '')
  form.bodyType = profile.bodyType || ''
  form.eyeColor = profile.eyeColor || ''
  form.hairColor = profile.hairColor || ''
  form.smoking = profile.smoking || ''
  form.drinking = profile.drinking || ''
  form.children = profile.children || ''
  form.zodiacSign = profile.zodiacSign || ''
  form.exercise = profile.exercise || ''

  setTimeout(() => { isReady.value = true }, 0)
}, { immediate: true })

const _autoSaveFields = ['gender', 'datingPurpose', 'bodyType', 'eyeColor', 'hairColor', 'smoking', 'drinking', 'children', 'zodiacSign', 'exercise']
_autoSaveFields.forEach(field => {
  watch(() => form[field], (val, old) => {
    if (!isReady.value || val === old) return
    debouncedSave()
  })
})

watch(() => form.dateOfBirth, (val, old) => {
  if (!isReady.value || !val || val === old) return
  debouncedSave()
})

watch([() => form.height, () => form.weight], ([newH, newW], [oldH, oldW]) => {
  if (!isReady.value) return
  if ((newH === null || newH === '') && oldH !== null && oldH !== '') debouncedSave()
  if ((newW === null || newW === '') && oldW !== null && oldW !== '') debouncedSave()
})

const photoSlots = computed(() => {
  const slots = []
  for (let i = 0; i < 3; i++) {
    slots.push(photos.value[i] || null)
  }
  return slots
})

const photoFileRefs = ref([null, null, null])

const triggerPhotoUpload = (index) => {
  const el = photoFileRefs.value[index]
  if (el) el.click()
}

const handleSlotUpload = async (event, index) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return

  isUploading.value = true
  const result = await profileStore.uploadPhotos(files)
  isUploading.value = false
  event.target.value = ''

  if (result.success) {
    toast.success('Photo uploaded. Validation in progress.')
    await profileStore.fetchMyProfile()
  } else {
    toast.error(result.message || 'Unable to upload photo.')
  }
}

let echoChannel = null

onMounted(async () => {
  await Promise.all([
    profileStore.fetchMyProfile(),
    subscriptionStore.fetchStatus(),
  ])

  const profileId = profileStore.myProfile?.profile_id
  if (!profileId) return

  const echo = initReverb()
  if (!echo) return

  echoChannel = echo.private(`profiles.${profileId}`)

  echoChannel.listen('.photo.validated', async (e) => {
    await new Promise(resolve => setTimeout(resolve, 500))
    await profileStore.fetchMyProfile()

    const { approved_count, rejected_count } = e.result

    if (approved_count > 0) {
      toast.success(`${approved_count} photo(s) approved`)
    }
    if (rejected_count > 0) {
      toast.error(`${rejected_count} photo(s) rejected (no face or multiple faces detected)`)
    }
  })
})

onUnmounted(() => {
  if (echoChannel) {
    echoChannel.stopListening('.photo.validated')
    echoChannel = null
  }
})

const saveProfile = async () => {
  enableError.value = ''
  missingFields.value = []
  isSaving.value = true

  const payload = {
    name: (form.name || '').trim(),
    date_of_birth: form.dateOfBirth || null,
    gender: form.gender || null,
    description: (form.description || '').trim(),
    dating_purpose: form.datingPurpose || null,
    body_type: form.bodyType || null,
    eye_color: form.eyeColor || null,
    hair_color: form.hairColor || null,
    smoking: form.smoking || null,
    drinking: form.drinking || null,
    children: form.children || null,
    zodiac_sign: form.zodiacSign || null,
    exercise: form.exercise || null,
    height: form.height === '' || form.height === null ? null : Number(form.height),
    weight: form.weight === '' || form.weight === null ? null : Number(form.weight)
  }

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

const updateLocation = async () => {
  await syncLocation()
  if (!geoError.value) {
    await profileStore.fetchMyProfile()
    toast.success('Location updated.')
  } else {
    toast.error(geoError.value)
  }
}
</script>

<template>
  <div class="page px-4 py-6 sm:px-6 lg:px-8">
    <div class="page-header max-w-6xl mx-auto mb-2"></div>

    <div v-if="isLoading && !myProfile" class="glass-panel p-5 text-sm text-slate-500">
      Loading profile...
    </div>

    <div v-else class="grid gap-[18px] lg:grid-cols-[1.15fr_0.85fr] max-w-6xl mx-auto lg:items-stretch">
      <div class="glass-panel p-6 flex flex-col h-full">
        <form id="profile-form" @submit.prevent class="flex flex-col gap-4">

          <div class="flex flex-col gap-[5px]">
            <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">Display Name</label>
            <div
              v-if="!editing.name"
              class="flex items-center justify-between rounded-[14px] border border-slate-200/70 bg-white/55 px-4 py-3 cursor-pointer hover:border-cyan-300 hover:bg-white/75 transition-all duration-150 group"
              @click="startEdit('name')"
            >
              <span class="text-[0.9rem]" :class="form.name ? 'text-slate-800' : 'text-slate-400'">
                {{ form.name || 'Your name' }}
              </span>
              <svg class="w-3.5 h-3.5 text-slate-300 group-hover:text-cyan-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 0L21 9l-7 7H9v-3z"/>
              </svg>
            </div>
            <BaseInput
              v-else
              ref="nameInputRef"
              v-model="form.name"
              type="text"
              placeholder="Your name"
              required
              @blur="stopEdit('name')"
              @keyup.enter.prevent="handleEnterKey($event, 'name')"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                placeholder="Empty"
                empty-label="Empty"
              />
            </div>
          </div>

          <div class="flex flex-col gap-[5px]">
            <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">About Me</label>
            <div
              v-if="!editing.description"
              class="w-full min-h-[80px] rounded-[14px] border border-slate-200/70 bg-white/55 px-4 py-3.5 cursor-pointer hover:border-cyan-300 hover:bg-white/75 transition-all duration-150 group relative"
              @click="startEdit('description')"
            >
              <p
                v-if="form.description"
                class="m-0 text-[0.9rem] text-slate-800 leading-[1.6] whitespace-pre-wrap line-clamp-5"
              >{{ form.description }}</p>
              <p v-else class="m-0 text-[0.9rem] text-slate-400">Tell them about yourself...</p>
              <svg class="absolute top-3 right-3 w-3.5 h-3.5 text-slate-300 group-hover:text-cyan-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 0L21 9l-7 7H9v-3z"/>
              </svg>
            </div>
            <div class="field__wrap" v-else>
              <textarea
                ref="descriptionInputRef"
                v-model="form.description"
                class="w-full h-[200px] min-h-[200px] max-h-[200px] resize-none leading-[1.6] bg-white/65 backdrop-blur-md border border-slate-300/60 rounded-[14px] px-4 py-3.5 text-[0.9rem] text-slate-800 outline-none transition-[border-color,box-shadow,background-color] duration-[180ms] ease-out focus:border-cyan-300 focus:ring-[3px] focus:ring-cyan-400/15 focus:bg-white/90"
                placeholder="Tell them about yourself..."
                maxlength="1200"
                @blur="stopEdit('description')"
                @keyup.enter.prevent="handleEnterKey($event, 'description')"
              ></textarea>
              <span class="text-[0.75rem] font-medium text-slate-400 text-right block mt-1">{{ (form.description || '').length }} / 1200</span>
            </div>
          </div>

          <div class="flex flex-col gap-3 py-1.5">
            <p class="m-0 text-[0.75rem] font-bold uppercase tracking-[0.08em] text-slate-500">More about you</p>
            <div class="flex flex-col gap-4">
              <div class="grid gap-6 sm:grid-cols-2">
                <SingleRangeSlider v-model="form.height" :min="130" :max="250" :fallback="170" label="Height" suffix=" cm" @change="handleSliderChange" />
                <SingleRangeSlider v-model="form.weight" :min="40" :max="150" :fallback="70" label="Weight" suffix=" kg" @change="handleSliderChange" />
              </div>

              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex flex-col gap-[5px]" v-for="(label, key) in {datingPurpose: 'Dating Purpose', bodyType: 'Body Type', eyeColor: 'Eye Color', hairColor: 'Hair Color', smoking: 'Smoking', drinking: 'Drinking', children: 'Children', zodiacSign: 'Zodiac Sign', exercise: 'Exercise'}" :key="key">
                  <label class="text-[0.78rem] font-semibold text-slate-700 tracking-[0.02em]">{{ label }}</label>
                  <GlassDropdown v-model="form[key]" :options="selectOptions[key]" placeholder="Empty" />
                </div>
              </div>
            </div>
          </div>
        </form>

        <div class="mt-5 flex-1 flex flex-col gap-2.5 rounded-2xl border border-slate-200/60 bg-white/45 p-4">
          <div class="flex items-center justify-between">
            <p class="m-0 text-[0.78rem] font-bold uppercase tracking-[0.08em] text-slate-500">Interests</p>
            <p class="m-0 text-[0.78rem] text-slate-500">{{ profileInterests.length }}/{{ maxInterests }} selected</p>
          </div>

          <div v-if="!profileInterests.length" class="text-[0.85rem] text-slate-500">
            Add a few interests to help with better matches.
          </div>

          <div class="flex flex-col gap-2" v-else>
            <div class="flex items-center gap-3">
              <span class="text-[0.72rem] font-bold uppercase tracking-[0.08em] text-pink-400">Selected</span>
              <div class="flex-1 h-px bg-pink-200/70"></div>
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2 max-h-[180px] overflow-y-auto pr-1 thin-scroll">
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

          <div class="flex-1 min-h-[120px] relative">
            <div class="absolute inset-0 grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2 overflow-y-auto content-start pr-1 thin-scroll">
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
          </div>
          
          <div class="mt-auto pt-2 border-t border-slate-200/20">
            <p v-if="!filteredInterestOptions.length" class="m-0 text-[0.75rem] text-slate-500">No matching interests.</p>
            <p class="m-0 text-[0.75rem] text-slate-500">
              Choose up to {{ remainingInterestSlots }} more interest{{ remainingInterestSlots === 1 ? '' : 's' }}.
            </p>
          </div>
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
              <div class="flex h-full w-full items-center justify-center text-4xl font-black text-cyan-200" v-else>
                No Photo
              </div>
            </div>
            <div class="border-t border-white/70 bg-white/90 p-4">
              <div class="flex items-baseline gap-2">
                <h2 class="text-xl font-bold text-slate-900">{{ previewName }}</h2>
                <span v-if="previewAge" class="text-base font-semibold text-slate-700">{{ previewAge }}</span>
              </div>
              <div class="mt-1 flex items-center justify-between gap-2 text-sm font-medium text-slate-600">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="flex items-center gap-1" v-if="previewLocation">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ previewLocation }}
                  </span>
                  <span class="text-slate-400 text-[0.8rem]" v-else>No location set</span>
                  <span v-if="previewGender">· {{ previewGender }}</span>
                </div>

                <button
                  type="button"
                  class="flex items-center gap-1 rounded-full border border-slate-200/80 bg-white/70 px-2.5 py-1 text-[0.72rem] font-semibold text-slate-600 hover:border-cyan-400 hover:text-cyan-600 hover:bg-cyan-50 transition disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                  :disabled="isLocating"
                  @click="updateLocation"
                  :title="previewLocation ? 'Update location' : 'Set location'"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-if="!isLocating">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" v-else>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                  </svg>
                  {{ isLocating ? 'Locating...' : previewLocation ? 'Update' : 'Set location' }}
                </button>
              </div>
              <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ previewBio }}</p>
            </div>
          </div>

          <div class="divider-glow" />

          <div class="flex flex-col gap-3">
            <p class="m-0 text-[0.95rem] font-semibold text-slate-900">Photos</p>
            <div class="grid grid-cols-3 gap-3">
              <div
                v-for="(slot, index) in photoSlots"
                :key="index"
                class="relative group"
              >
                <div class="relative overflow-hidden rounded-2xl border border-slate-200/75 bg-white/80 shadow-sm aspect-[3/4]" v-if="slot">
                  <img :src="slot.url" alt="Profile photo" class="block w-full h-full object-cover" />
                  <button
                    type="button"
                    class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/35 transition-all duration-200 rounded-2xl"
                    @click="deletePhoto(slot.id)"
                  >
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col items-center gap-1">
                      <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      <span class="text-[0.7rem] font-semibold text-white">Delete</span>
                    </span>
                  </button>
                  <span class="absolute bottom-1.5 left-1.5 rounded-full bg-black/50 px-1.5 py-0.5 text-[0.62rem] font-bold text-white backdrop-blur-sm">
                    {{ index + 1 }}
                  </span>
                </div>

                <label
                  class="flex aspect-[3/4] cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 transition-all duration-150 hover:border-cyan-400 hover:bg-cyan-50/60 active:scale-[0.97]"
                  :class="{ 'opacity-60 pointer-events-none': isUploading }"
                  v-else
                >
                  <input
                    type="file"
                    class="sr-only"
                    accept="image/*"
                    :ref="el => photoFileRefs[index] = el"
                    @change="(e) => handleSlotUpload(e, index)"
                  />
                  <div class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-slate-200 bg-white text-slate-400 transition-all group-hover:border-cyan-400 group-hover:text-cyan-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-if="!isUploading">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" v-else>
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                  </div>
                  <span class="text-[0.7rem] font-semibold text-slate-400">
                    {{ index === 0 ? 'Main photo' : `Photo ${index + 1}` }}
                  </span>
                </label>
              </div>
            </div>
            <p class="m-0 text-[0.75rem] text-slate-400">Add at least one photo to enable discovery.</p>
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

            <p class="m-0 text-[0.78rem] text-rose-500" v-if="subscriptionError">{{ subscriptionError }}</p>
          </div>

          <div class="divider-glow" />

          <div class="flex flex-col gap-2">
            <BaseButton variant="outline" full @click="toggleVisibility">
              {{ isEnabled ? 'Disable Profile' : 'Enable Profile' }}
            </BaseButton>
            <p class="m-0 text-[0.85rem] text-rose-500" v-if="enableError">{{ enableError }}</p>
            <p class="m-0 text-[0.78rem] text-slate-500" v-if="missingFieldLabels.length">
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
  </div>
</template>

<style scoped>
.tag-info {
  @apply px-2.5 py-1.5 rounded-full bg-white/10 backdrop-blur-sm text-white text-[0.73rem] font-medium border border-white/15 transition-colors hover:bg-white/20;
}
.tag-interest {
  @apply px-2.5 py-1.5 rounded-full bg-cyan-400/20 backdrop-blur-sm text-white text-[0.73rem] font-medium border border-cyan-300/25;
}
.thin-scroll::-webkit-scrollbar {
  width: 4px;
}
.thin-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.thin-scroll::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 10px;
}
</style>