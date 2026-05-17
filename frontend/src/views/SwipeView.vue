<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profile.js'
import { useNotificationsStore } from '@/stores/notifications.js'
import { useGeolocation } from '@/composables/useGeolocation.js'
import { profilesApi } from '@/api/profiles.js'
import { useToast } from '@/composables/useToast.js'
import SwipeCard from '@/components/SwipeCard.vue'
import ActionButtons from '@/components/ActionButtons.vue'
import MatchModal from '@/components/MatchModal.vue'
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const profileStore = useProfileStore()
const notificationsStore = useNotificationsStore()
const { toast } = useToast()
const { syncLocation, locationError } = useGeolocation()

const cardRef = ref(null)
const deckError = ref(null)

const isMatch = ref(false)
const matchData = ref(null)
const pendingMatchId = ref(null)

const currentProfile = computed(() => profileStore.currentCard)
const isLoading = computed(() => profileStore.isLoadingDeck)
const myProfile = computed(() => profileStore.myProfile)

const getProfileUserId = (profile) => profile?.user_id || profile?.userId || profile?.user?.id || null

const fieldLabels = {
  name: 'Name',
  date_of_birth: 'Birth date',
  gender: 'Gender',
  city: 'City',
  photos: 'Photo',
}

const missingFieldsLabel = computed(() => {
  const missing = (deckError.value?.missingFields || []).map((field) => fieldLabels[field] || field)
  if (myProfile.value && (!myProfile.value.photos || myProfile.value.photos.length === 0) && !missing.includes('Photo')) {
    missing.push('Photo')
  }
  return missing
})

const loadDeck = async () => {
  deckError.value = null
  const result = await profileStore.fetchDeck(true)
  if (result?.success === false) {
    if (result.status === 404) {
      return
    }
    deckError.value = result
  }
}

const checkForMatch = async () => {
  const { success } = await notificationsStore.fetchAll()
  if (!success) return

  const matchNotification = [...notificationsStore.notifications]
    .filter((notif) => notif.type?.includes('MatchNotification'))
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]

  if (!matchNotification) return

  const matchedId = matchNotification.data?.matched_user_profile_id
  if (!matchedId) return

  try {
    const { data } = await profilesApi.getById(matchedId)
    matchData.value = {
      user: data.profile,
      match: myProfile.value || null,
    }
    pendingMatchId.value = matchNotification.id
    isMatch.value = true
  } catch (err) {
    console.error('Failed to fetch match profile', err)
  }
}

onMounted(async () => {
  await syncLocation()
  await profileStore.fetchMyProfile()
  await loadDeck()
})

const handleSwipe = async (direction) => {
  if (!currentProfile.value) return
  const isLiked = direction === 'like'
  const userId = getProfileUserId(currentProfile.value)
  if (!userId) {
    toast.error('Swipe target is missing an account id.')
    return
  }

  const ok = await profileStore.swipeCard(userId, isLiked)
  if (!ok) toast.error('Swipe failed. Try again.')
  if (isLiked) {
    await checkForMatch()
  }
}

const handleAction = (action) => {
  if (!cardRef.value) return
  const isLiked = action === 'like'
  cardRef.value.triggerSwipe(isLiked)
}

const goToProfile = () => {
  router.push({ name: 'profile' })
}

const enableProfile = async () => {
  const result = await profileStore.toggleProfileVisibility(true)
  if (result.success) {
    toast.success('Profile enabled.')
    await loadDeck()
  } else {
    toast.error(result.message || 'Unable to enable profile.')
  }
}

const hideMatch = async () => {
  isMatch.value = false
  matchData.value = null
  if (pendingMatchId.value) {
    await notificationsStore.markAsRead(pendingMatchId.value)
    pendingMatchId.value = null
  }
}
</script>

<template>
  <div class="page page--center page--discover">
    <div class="page-header animate-fade-in-up">
      <!-- Header removed as requested -->
    </div>

    <div class="flex flex-col items-center gap-4 sm:gap-5">
      <div v-if="locationError" class="chip">
        {{ locationError }}
      </div>

      <div v-if="deckError" class="glass-panel max-w-md px-6 py-6 text-center animate-fade-in-up">
        <p class="text-lg font-semibold mb-2">Action needed</p>
        <p class="text-sm text-white/70">{{ deckError.message }}</p>
        <div v-if="missingFieldsLabel.length" class="mt-4 text-sm text-white/70">
          Missing: {{ missingFieldsLabel.join(', ') }}
        </div>
        <div class="flex flex-col sm:flex-row gap-3 mt-6">
          <BaseButton variant="primary" full @click="goToProfile">Complete Profile</BaseButton>
          <BaseButton v-if="deckError.status === 403" variant="secondary" full @click="enableProfile">
            Enable Profile
          </BaseButton>
        </div>
      </div>

      <div v-else-if="isLoading" class="glass-panel px-6 py-4 text-sm text-white/70 animate-fade-in-up">
        Loading profiles...
      </div>

      <div v-else-if="currentProfile" class="discover-stack w-full max-w-6xl px-2 sm:px-4 relative flex flex-col items-center gap-3 sm:gap-4 animate-fade-in-up">
        <SwipeCard ref="cardRef" :profile="currentProfile" @swiped="handleSwipe" />
        <div class="chip">Swipe or tap the buttons below</div>
        <ActionButtons @action="handleAction" />
      </div>

      <div v-else class="glass-panel max-w-2xl px-6 py-8 text-center animate-fade-in-up">
        <p class="text-lg font-semibold mb-2">No more profiles found</p>
        <p class="text-sm text-white/70">Try adjusting your discovery settings.</p>
      </div>
    </div>

    <MatchModal
      v-if="isMatch"
      :match-data="matchData"
      @close="hideMatch"
    />
  </div>
</template>
