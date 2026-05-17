<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useProfileStore } from '@/stores/profile.js'
import { useToast } from '@/composables/useToast.js'
import BaseInput from '@/components/BaseInput.vue'
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useProfileStore()
const { toast } = useToast()

const form = reactive({
  name: '',
  dateOfBirth: '',
  gender: '',
  description: '',
})

const isSaving = ref(false)
const isUploading = ref(false)
const enableError = ref('')
const missingFields = ref([])

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
  const gender = myProfile.value?.gender
  if (gender === 'woman') return 'Woman'
  if (gender === 'man') return 'Man'
  return null
})

const missingFieldLabels = computed(() =>
  missingFields.value.map((field) => fieldLabels[field] || field)
)

watch(myProfile, (profile) => {
  if (!profile) return
  form.name = profile.name || ''
  form.dateOfBirth = profile.date_of_birth || ''
  form.gender = profile.gender || ''
  form.description = profile.description || ''
}, { immediate: true })

onMounted(async () => {
  await profileStore.fetchMyProfile()
})

const saveProfile = async () => {
  enableError.value = ''
  missingFields.value = []
  isSaving.value = true

  const result = await profileStore.updateMyProfile({
    name: form.name,
    date_of_birth: form.dateOfBirth,
    gender: form.gender,
    description: form.description || null,
  })

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
</script>

<template>
  <div class="page">
    <div class="page-header">
      <p class="eyebrow">Profile</p>
      <h1 class="page-title">Your MatchFlow vibe</h1>
      <div class="flex flex-wrap gap-2 mt-2">
        <span class="chip">Completion: {{ completionPct }}%</span>
        <span class="chip">{{ isEnabled ? 'Profile enabled' : 'Profile disabled' }}</span>
        <span v-if="myProfile?.age" class="chip">Age: {{ myProfile.age }}</span>
        <span v-if="locationLabel" class="chip">{{ locationLabel }}</span>
      </div>
      <div class="w-full max-w-xl mt-4">
        <div class="h-2 rounded-full bg-white/10">
          <div class="h-2 rounded-full" :style="{ width: `${completionPct}%`, background: 'var(--gradient-primary)' }" />
        </div>
      </div>
    </div>

    <div v-if="isLoading && !myProfile" class="glass-panel px-6 py-4 text-sm text-white/70">
      Loading profile...
    </div>

    <div v-else class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
      <div class="glass-panel p-6 md:p-8">
        <form @submit.prevent="saveProfile" class="space-y-5">
          <BaseInput v-model="form.name" type="text" label="Display Name" placeholder="Your name" required />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput v-model="form.dateOfBirth" type="date" label="Birth Date" required />
            <div>
              <label class="block text-xs uppercase tracking-[0.3em] text-white/70 mb-2" for="gender">Gender</label>
              <select
                id="gender"
                v-model="form.gender"
                class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-violet-400"
              >
                <option value="" disabled>Select</option>
                <option value="woman">Woman</option>
                <option value="man">Man</option>
              </select>
            </div>
          </div>

          <BaseInput v-model="form.description" type="textarea" rows="4" label="About Me" placeholder="Tell them about your energy..." />

          <BaseButton type="submit" variant="primary" full :loading="isSaving">
            Save Changes
          </BaseButton>
        </form>
      </div>

      <div class="glass-panel p-6 md:p-8 flex flex-col gap-4">
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
              <span v-if="previewGender"> • {{ previewGender }}</span>
            </div>
            <p class="profile-preview__bio">{{ previewBio }}</p>
          </div>
        </div>

        <div class="divider-glow" />

        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm uppercase tracking-[0.3em] text-white/60">Photos</p>
            <p class="text-lg font-semibold">Show your best angles</p>
          </div>
          <label class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border border-white/10 bg-white/5 hover:bg-white/10 transition cursor-pointer">
            <input type="file" class="hidden" multiple accept="image/*" @change="handleUpload" />
            {{ isUploading ? 'Uploading...' : 'Add Photos' }}
          </label>
        </div>

        <div v-if="photos.length" class="grid grid-cols-2 gap-3">
          <div v-for="photo in photos" :key="photo.id" class="relative rounded-2xl overflow-hidden border border-white/10">
            <img :src="photo.url" alt="Profile photo" class="w-full h-36 object-cover" />
            <button
              type="button"
              class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full"
              @click="deletePhoto(photo.id)"
            >
              Delete
            </button>
          </div>
        </div>
        <div v-else class="text-sm text-white/60">
          Add at least one photo to enable discovery.
        </div>

        <div class="divider-glow" />

        <div class="flex flex-col gap-3">
          <BaseButton variant="secondary" full @click="toggleVisibility">
            {{ isEnabled ? 'Disable Profile' : 'Enable Profile' }}
          </BaseButton>
          <p v-if="enableError" class="text-sm text-rose-200">{{ enableError }}</p>
          <p v-if="missingFieldLabels.length" class="text-xs text-white/60">
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
.profile-preview {
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--glass-border);
  background: rgba(255, 255, 255, 0.04);
}

.profile-preview__media {
  height: 240px;
  background: linear-gradient(160deg, rgba(165, 139, 255, 0.25), rgba(255, 176, 170, 0.2));
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
  font-size: 0.9rem;
}

.profile-preview__overlay {
  position: absolute;
  inset: auto 0 0 0;
  padding: 18px;
  background: linear-gradient(0deg, rgba(10, 6, 20, 0.88), rgba(10, 6, 20, 0.15));
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.profile-preview__name {
  display: flex;
  align-items: baseline;
  gap: 8px;
  font-size: 1.2rem;
  font-weight: 700;
}

.profile-preview__age {
  font-weight: 400;
  color: var(--text-secondary);
}

.profile-preview__meta {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.profile-preview__bio {
  font-size: 0.85rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}
</style>
