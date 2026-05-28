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
  form.dateOfBirth = profile.date_of_birth || ''
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
</script>

<template>
  <div class="page min-h-screen bg-transparent text-slate-900 px-4 py-6 sm:px-6 lg:px-8">
    <div class="page-header max-w-6xl mx-auto mb-8">
      <p class="eyebrow">Profile</p>
      <h1 class="page-title">Your profile</h1>
      <div class="profile-chips">
        <span class="chip">Completion: {{ completionPct }}%</span>
        <span class="chip">{{ isEnabled ? 'Enabled' : 'Disabled' }}</span>
        <span v-if="myProfile?.age" class="chip">Age: {{ myProfile.age }}</span>
        <span v-if="locationLabel" class="chip">{{ locationLabel }}</span>
      </div>
      <div class="profile-progress">
        <div class="profile-progress__bar" :style="{ width: `${completionPct}%` }" />
      </div>
    </div>

    <div v-if="isLoading && !myProfile" class="glass-panel profile-loading">
      Loading profile...
    </div>

    <div v-else class="profile-grid max-w-6xl mx-auto gap-8 lg:gap-10">
      <div class="glass-panel profile-form-panel rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="saveProfile" class="profile-form">
          <BaseInput v-model="form.name" type="text" label="Display Name" placeholder="Your name" required />

          <div class="profile-form__row">
            <BaseInput v-model="form.dateOfBirth" type="date" label="Birth Date" required />
            <div class="profile-form__field">
              <label class="profile-form__label" for="gender">Gender</label>
              <select id="gender" v-model="form.gender" class="profile-form__select">
                <option value="" disabled>Select</option>
                <option value="woman">Woman</option>
                <option value="man">Man</option>
              </select>
            </div>
          </div>

          <BaseInput v-model="form.description" type="textarea" rows="4" label="About Me" placeholder="Tell them about yourself..." />

          <div class="profile-section">
            <p class="profile-section__title">More about you</p>
            <div class="profile-form__grid">
              <div class="profile-form__field">
                <label class="profile-form__label" for="dating-purpose">Dating Purpose</label>
                <select id="dating-purpose" v-model="form.datingPurpose" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.datingPurpose" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="body-type">Body Type</label>
                <select id="body-type" v-model="form.bodyType" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.bodyType" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <BaseInput v-model="form.height" type="number" label="Height (cm)" />
              <BaseInput v-model="form.weight" type="number" label="Weight (kg)" />

              <div class="profile-form__field">
                <label class="profile-form__label" for="eye-color">Eye Color</label>
                <select id="eye-color" v-model="form.eyeColor" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.eyeColor" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="hair-color">Hair Color</label>
                <select id="hair-color" v-model="form.hairColor" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.hairColor" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="smoking">Smoking</label>
                <select id="smoking" v-model="form.smoking" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.smoking" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="drinking">Drinking</label>
                <select id="drinking" v-model="form.drinking" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.drinking" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="children">Children</label>
                <select id="children" v-model="form.children" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.children" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="zodiac">Zodiac Sign</label>
                <select id="zodiac" v-model="form.zodiacSign" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.zodiacSign" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="profile-form__field">
                <label class="profile-form__label" for="exercise">Exercise</label>
                <select id="exercise" v-model="form.exercise" class="profile-form__select">
                  <option value="">Empty</option>
                  <option v-for="opt in selectOptions.exercise" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <BaseButton type="submit" variant="primary" full :loading="isSaving">
            Save Changes
          </BaseButton>
        </form>
      </div>

      <div class="glass-panel profile-side-panel rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="profile-preview">
          <div class="profile-preview__media">
            <img v-if="previewPhoto" :src="previewPhoto" alt="Profile preview" />
            <div v-else class="profile-preview__placeholder">Add a photo</div>
          </div>
          <div class="profile-preview__overlay">
            <div class="profile-preview__name">
              <span>{{ previewName }}</span>
              <span v-if="previewAge" class="profile-preview__age">{{ previewAge }}</span>
            </div>
            <div class="profile-preview__meta">
              <span>{{ previewLocation }}</span>
              <span v-if="previewGender"> · {{ previewGender }}</span>
            </div>
            <p class="profile-preview__bio">{{ previewBio }}</p>
          </div>
        </div>

        <div class="divider-glow" />

        <div class="profile-photos-header">
          <div>
            <p class="profile-photos-header__title">Photos</p>
          </div>
          <label class="profile-upload-btn">
            <input type="file" class="sr-only" multiple accept="image/*" @change="handleUpload" />
            {{ isUploading ? 'Uploading...' : 'Add Photos' }}
          </label>
        </div>

        <div v-if="photos.length" class="profile-photos-grid">
          <div v-for="photo in photos" :key="photo.id" class="profile-photo-card">
            <img :src="photo.url" alt="Profile photo" />
            <button type="button" class="profile-photo-card__delete" @click="deletePhoto(photo.id)">
              Delete
            </button>
          </div>
        </div>
        <div v-else class="profile-photos-empty">
          Add at least one photo to enable discovery.
        </div>

        <div class="divider-glow" />

        <div class="profile-interests">
          <div class="profile-interests__header">
            <p class="profile-interests__label">Interests</p>
            <p class="profile-interests__meta">{{ profileInterests.length }}/{{ maxInterests }} selected</p>
          </div>

          <div v-if="!profileInterests.length" class="profile-interests__empty">
            Add a few interests to help with better matches.
          </div>

          <div v-else class="profile-interests__list">
            <button
              v-for="interest in profileInterests"
              :key="interest.id"
              type="button"
              class="interest-card interest-card--selected"
              :disabled="isUpdatingInterests"
              @click="removeInterest(interest.id)"
            >
              <span>{{ interestLabel(interest.interest) }}</span>
              <span class="interest-card__icon">×</span>
            </button>
          </div>

          <BaseInput
            v-model="interestQuery"
            type="text"
            label="Find interests"
            placeholder="Type to search"
          />

          <div class="profile-interests__grid scrollable-grid">
            <button
              v-for="opt in filteredInterestOptions"
              :key="opt.value"
              type="button"
              class="interest-card"
              :disabled="remainingInterestSlots === 0 || isUpdatingInterests"
              @click="addInterest(opt.value)"
            >
              <span>{{ opt.label }}</span>
              <span class="interest-card__icon">+</span>
            </button>
          </div>
          <p v-if="!filteredInterestOptions.length" class="profile-interests__hint">No matching interests.</p>
          <p class="profile-interests__hint">
            Choose up to {{ remainingInterestSlots }} more interest{{ remainingInterestSlots === 1 ? '' : 's' }}.
          </p>
        </div>

        <div class="divider-glow" />

        <div class="profile-premium">
          <div class="profile-premium__header">
            <div>
              <p class="profile-premium__eyebrow">Premium</p>
              <h3 class="profile-premium__title">Upgrade your discovery</h3>
            </div>
            <span class="chip">{{ isPremium ? 'Active' : 'Locked' }}</span>
          </div>
          <p class="profile-premium__text">
            Get advanced filters and deeper matches with Premium membership.
          </p>
          <BaseButton
            v-if="!isPremium"
            variant="primary"
            full
            :loading="isSubscriptionLoading"
            @click="goPremium"
          >
            Get Premium
          </BaseButton>
          <BaseButton
            v-else
            variant="secondary"
            full
            :loading="isSubscriptionLoading"
            @click="cancelPremium"
          >
            Cancel Subscription
          </BaseButton>
          <p v-if="subscriptionError" class="profile-premium__error">{{ subscriptionError }}</p>
        </div>

        <div class="divider-glow" />

        <div class="profile-actions">
          <BaseButton variant="secondary" full @click="toggleVisibility">
            {{ isEnabled ? 'Disable Profile' : 'Enable Profile' }}
          </BaseButton>
          <p v-if="enableError" class="profile-actions__error">{{ enableError }}</p>
          <p v-if="missingFieldLabels.length" class="profile-actions__missing">
            Missing fields: {{ missingFieldLabels.join(', ') }}
          </p>
        </div>

        <div class="divider-glow" />

        <BaseButton variant="danger" full @click="handleLogout">
          Log Out
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 6px;
}

.profile-progress {
  width: 100%;
  max-width: 480px;
  height: 6px;
  border-radius: 999px;
  background: var(--border-color);
  margin-top: 10px;
  overflow: hidden;
}

.profile-progress__bar {
  height: 100%;
  border-radius: 999px;
  background: var(--gradient-primary);
  transition: width 0.4s var(--ease-smooth);
}

.profile-loading {
  padding: 20px 24px;
  font-size: 0.88rem;
  color: var(--text-muted);
}

.profile-grid {
  display: grid;
  gap: 18px;
}

@media (min-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1.15fr 0.85fr;
  }
}

.profile-form-panel {
  padding: 24px;
}

.profile-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.profile-form__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.profile-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 6px 0;
}

.profile-section__title {
  margin: 0;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-muted);
}

.profile-form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.profile-form__field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.profile-form__label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-secondary);
  letter-spacing: 0.02em;
}

.profile-form__select {
  width: 100%;
  background: var(--color-bg-elevated);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 11px 14px;
  font-size: 0.92rem;
  color: var(--text-primary);
}

.profile-form__select:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
}

.profile-side-panel {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Preview card */
.profile-preview {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-color);
  background: var(--color-bg);
}

.profile-preview__media {
  height: 200px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(14, 165, 233, 0.05));
}

.profile-preview__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-preview__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 0.88rem;
}

.profile-preview__overlay {
  position: absolute;
  inset: auto 0 0 0;
  padding: 16px;
  background: linear-gradient(0deg, rgba(0, 0, 0, 0.65), transparent);
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.profile-preview__name {
  display: flex;
  align-items: baseline;
  gap: 6px;
  font-size: 1.1rem;
  font-weight: 700;
}

.profile-preview__age {
  font-weight: 400;
  opacity: 0.85;
}

.profile-preview__meta {
  font-size: 0.8rem;
  opacity: 0.8;
}

.profile-preview__bio {
  font-size: 0.8rem;
  opacity: 0.85;
  line-height: 1.4;
  margin: 0;
}

/* Photos */
.profile-photos-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.profile-photos-header__label {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin: 0 0 2px;
}

.profile-photos-header__title {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.profile-upload-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 7px 14px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid var(--border-color);
  background: var(--color-bg);
  color: var(--text-primary);
  cursor: pointer;
  transition: background var(--duration-fast), border-color var(--duration-fast);
}

.profile-upload-btn:hover {
  background: var(--color-accent-muted);
  border-color: var(--color-accent);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.profile-photos-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.profile-photo-card {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.profile-photo-card img {
  width: 100%;
  height: 130px;
  object-fit: cover;
  display: block;
}

.profile-photo-card__delete {
  position: absolute;
  top: 6px;
  right: 6px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  font-size: 0.7rem;
  padding: 3px 8px;
  border: none;
  border-radius: 999px;
  cursor: pointer;
}

.profile-photos-empty {
  font-size: 0.85rem;
  color: var(--text-muted);
}

/* Interests */
.profile-interests {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.profile-interests__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.profile-interests__label {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-muted);
}

.profile-interests__meta {
  margin: 0;
  font-size: 0.78rem;
  color: var(--text-muted);
}

.profile-interests__empty {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.profile-interests__list,
.profile-interests__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 8px;
}

.scrollable-grid {
  max-height: 250px;
  overflow-y: auto;
  align-content: flex-start;
  padding-right: 4px;
}

.interest-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  background: var(--color-bg);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
  cursor: pointer;
  transition: border-color var(--duration-fast), background var(--duration-fast), transform var(--duration-fast);
}

.interest-card:hover:not(:disabled) {
  border-color: var(--color-accent);
  background: var(--color-accent-muted);
  transform: translateY(-1px);
}

.interest-card:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.interest-card--selected {
  border-color: rgba(244, 63, 94, 0.3);
  background: rgba(244, 63, 94, 0.08);
}

.interest-card__icon {
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-size: 0.9rem;
  font-weight: 700;
}

.interest-card--selected .interest-card__icon {
  background: rgba(244, 63, 94, 0.15);
  color: var(--color-rose);
}

.profile-interests__hint {
  margin: 0;
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Premium */
.profile-premium {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px dashed rgba(14, 165, 233, 0.45);
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(34, 197, 94, 0.05));
}

.profile-premium__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.profile-premium__eyebrow {
  margin: 0 0 4px;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--text-muted);
  font-weight: 700;
}

.profile-premium__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
}

.profile-premium__text {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.profile-premium__error {
  margin: 0;
  font-size: 0.78rem;
  color: var(--color-rose);
}

.profile-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.profile-actions__error {
  font-size: 0.85rem;
  color: var(--color-rose);
  margin: 0;
}

.profile-actions__missing {
  font-size: 0.78rem;
  color: var(--text-muted);
  margin: 0;
}
</style>
