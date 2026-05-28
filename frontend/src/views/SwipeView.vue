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

const getProfileUserId = (profile) =>
  profile?.user_id || profile?.userId || profile?.user?.id || null

const fieldLabels = {
  name: 'Name',
  date_of_birth: 'Birth date',
  gender: 'Gender',
  city: 'City',
  country: 'Location',
  photos: 'Photo',
}

const missingFieldsLabel = computed(() => {
  const missing = (deckError.value?.missingFields || []).map((f) => fieldLabels[f] || f)
  if (
    myProfile.value &&
    (!myProfile.value.photos || myProfile.value.photos.length === 0) &&
    !missing.includes('Photo')
  ) {
    missing.push('Photo')
  }
  return missing
})

const loadDeck = async () => {
  deckError.value = null
  const result = await profileStore.fetchDeck(true)
  if (result?.success === false) {
    if (result.status === 404) return
    deckError.value = result
  }
}

const checkForMatch = async () => {
  const { success } = await notificationsStore.fetchAll()
  if (!success) return

  const matchNotification = [...notificationsStore.notifications]
    .filter((n) => n.type?.includes('MatchNotification'))
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
  if (isLiked) await checkForMatch()
}

const handleAction = (action) => {
  if (action === 'undo') return
  if (!cardRef.value) return
  cardRef.value.triggerSwipe(action === 'like')
}

const canUndo = computed(() => !!profileStore.lastSwipedId)

const handleUndo = async () => {
  const result = await profileStore.rollbackSwipe()
  if (result.success) {
    toast.success('Swipe undone.')
  } else {
    toast.error(result.message || 'Unable to undo swipe.')
  }
}

const goToProfile = () => router.push({ name: 'profile' })

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
    <div class="discover-content">
      <div v-if="locationError" class="chip">{{ locationError }}</div>

      <div v-if="deckError" class="glass-panel discover-notice animate-fade-in-up">
        <p class="discover-notice__title">Action needed</p>
        <p class="discover-notice__text">{{ deckError.message }}</p>
        <div v-if="missingFieldsLabel.length" class="discover-notice__text" style="margin-top: 8px">
          Missing: {{ missingFieldsLabel.join(', ') }}
        </div>
        <div class="discover-notice__actions">
          <BaseButton variant="primary" full @click="goToProfile">Complete Profile</BaseButton>
          <BaseButton v-if="deckError.status === 403" variant="secondary" full @click="enableProfile">
            Enable Profile
          </BaseButton>
        </div>
      </div>

      <div v-else-if="isLoading" class="glass-panel discover-notice animate-fade-in-up">
        <p class="discover-notice__text">Loading profiles...</p>
      </div>

      <div v-else-if="currentProfile" class="discover-stack animate-fade-in-up">
        <SwipeCard ref="cardRef" :profile="currentProfile" @swiped="handleSwipe" />
        <ActionButtons :show-undo="canUndo" @action="handleAction" @undo="handleUndo" style="align-self: center" />
      </div>

      <div v-else class="glass-panel discover-notice animate-fade-in-up">
        <p class="discover-notice__title">No more profiles found</p>
        <p class="discover-notice__text">Try adjusting your discovery settings.</p>
        <div v-if="canUndo" class="discover-notice__actions" style="justify-content: center; margin-top: 15px;">
          <BaseButton variant="secondary" @click="handleUndo">Undo Last Swipe</BaseButton>
        </div>
      </div>
    </div>

    <MatchModal v-if="isMatch" :match-data="matchData" @close="hideMatch" />
  </div>
</template>

<style scoped>
.discover-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  width: 100%;
}

.discover-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  width: 100%;
  max-width: 960px;
}

.discover-notice {
  max-width: 420px;
  padding: 24px;
  text-align: center;
}

.discover-notice__title {
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--text-primary);
  margin: 0 0 6px;
}

.discover-notice__text {
  font-size: 0.88rem;
  color: var(--text-secondary);
  margin: 0;
}

.discover-notice__actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 18px;
}

@media (min-width: 640px) {
  .discover-notice__actions {
    flex-direction: row;
  }
}
</style>
