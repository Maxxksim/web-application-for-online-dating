<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profile.js'
import { useSubscriptionStore } from '@/stores/subscription.js'
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
const subscriptionStore = useSubscriptionStore()
const notificationsStore = useNotificationsStore()
const { toast } = useToast()
const { syncLocation, locationError } = useGeolocation()

const cardRef = ref(null)
const deckError = ref(null)

const isMatch = ref(false)
const matchData = ref(null)
const pendingMatchId = ref(null)
const showPremiumModal = ref(false)

const currentProfile = computed(() => profileStore.currentCard)
const isLoading = computed(() => profileStore.isLoadingDeck)
const myProfile = computed(() => profileStore.myProfile)
const isPremium = computed(() => subscriptionStore.isPremium)

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
  await Promise.all([
    profileStore.fetchMyProfile(),
    subscriptionStore.fetchStatus(),
  ])
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
  if (!isPremium.value) {
    showPremiumModal.value = true
    return
  }
  const result = await profileStore.rollbackSwipe()
  if (result.success) {
    toast.success('Swipe undone.')
  } else {
    toast.error(result.message || 'Unable to undo swipe.')
  }
}

const closePremiumModal = () => {
  showPremiumModal.value = false
}

const goPremium = async () => {
  showPremiumModal.value = false
  await subscriptionStore.startCheckout()
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
  <div class="relative min-h-screen flex flex-col items-center justify-center px-6 pb-24 pt-9">
    <div class="flex flex-col items-center gap-[14px] w-full">
      <div v-if="locationError" class="inline-flex items-center gap-[6px] px-[10px] py-[4px] rounded-full border border-[rgba(226,232,240,0.75)] bg-white/75 text-[0.72rem] font-semibold text-slate-600">
        {{ locationError }}
      </div>

      <div v-if="deckError" class="max-w-[420px] p-6 text-center rounded-[32px] border border-[rgba(226,232,240,0.75)] bg-[rgba(255,255,255,0.72)] shadow-[0_4px_16px_rgba(15,23,42,0.06),0_1px_4px_rgba(15,23,42,0.04)] backdrop-blur-[20px] animate-fade-in-up">
        <p class="font-semibold text-[1.05rem] text-slate-900 m-0 mb-1.5">Action needed</p>
        <p class="text-[0.88rem] text-slate-700 m-0">{{ deckError.message }}</p>
        <div v-if="missingFieldsLabel.length" class="text-[0.88rem] text-slate-700 m-0 mt-2">
          Missing: {{ missingFieldsLabel.join(', ') }}
        </div>
        <div class="flex flex-col sm:flex-row gap-2.5 mt-[18px]">
          <BaseButton variant="primary" full @click="goToProfile">Complete Profile</BaseButton>
          <BaseButton v-if="deckError.status === 403" variant="secondary" full @click="enableProfile">
            Enable Profile
          </BaseButton>
        </div>
      </div>

      <div v-else-if="isLoading" class="max-w-[420px] p-6 text-center rounded-[32px] border border-[rgba(226,232,240,0.75)] bg-[rgba(255,255,255,0.72)] shadow-[0_4px_16px_rgba(15,23,42,0.06),0_1px_4px_rgba(15,23,42,0.04)] backdrop-blur-[20px] animate-fade-in-up">
        <p class="text-[0.88rem] text-slate-700 m-0">Loading profiles...</p>
      </div>

      <div v-else-if="currentProfile" class="flex flex-col items-center gap-3 w-full max-w-[960px] animate-fade-in-up">
        <SwipeCard ref="cardRef" :profile="currentProfile" @swiped="handleSwipe" />
        <ActionButtons :show-undo="canUndo" @action="handleAction" @undo="handleUndo" style="align-self: center" />
      </div>

      <div v-else class="max-w-[420px] p-6 text-center rounded-[32px] border border-[rgba(226,232,240,0.75)] bg-[rgba(255,255,255,0.72)] shadow-[0_4px_16px_rgba(15,23,42,0.06),0_1px_4px_rgba(15,23,42,0.04)] backdrop-blur-[20px] animate-fade-in-up">
        <p class="font-semibold text-[1.05rem] text-slate-900 m-0 mb-1.5">No more profiles found</p>
        <p class="text-[0.88rem] text-slate-700 m-0">Try adjusting your discovery settings.</p>
        <div v-if="canUndo" class="flex flex-col sm:flex-row gap-2.5 mt-[15px] justify-center">
          <BaseButton variant="secondary" @click="handleUndo">Undo Last Swipe</BaseButton>
        </div>
      </div>
    </div>

    <MatchModal v-if="isMatch" :match-data="matchData" @close="hideMatch" />

    <!-- Premium Upsell Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showPremiumModal" class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-4 sm:p-6" style="pointer-events: auto;">
          <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closePremiumModal"></div>
          
          <div class="relative w-full max-w-[340px] rounded-[32px] border border-white/60 bg-white/90 p-6 shadow-[0_24px_64px_rgba(15,23,42,0.2),0_8px_24px_rgba(15,23,42,0.08)] backdrop-blur-2xl animate-fade-in-up overflow-hidden mb-8 sm:mb-0">
            <!-- Decorative blur -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-32 w-32 rounded-full bg-gradient-to-bl from-amber-400/30 to-yellow-500/10 blur-2xl"></div>
            
            <button @click="closePremiumModal" class="absolute top-4 right-4 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100/80 text-slate-500 transition hover:bg-slate-200">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>

            <div class="relative z-10 flex flex-col items-center gap-4 text-center mt-2">
              <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 shadow-lg shadow-amber-200/60 ring-4 ring-amber-50">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 7v6h6" />
                  <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" />
                </svg>
              </div>
              
              <div>
                <h3 class="m-0 text-[1.15rem] font-bold text-slate-900">Undo your last swipe</h3>
                <p class="m-0 mt-2 text-[0.88rem] leading-relaxed text-slate-600">
                  Made a mistake? Upgrade to Premium to get a second chance and undo your swipes.
                </p>
              </div>
              
              <div class="mt-2 w-full flex flex-col gap-2.5">
                <button @click="goPremium" :disabled="subscriptionStore.isLoading" class="relative flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 py-3.5 px-4 text-[0.9rem] font-bold text-white shadow-md transition hover:from-slate-800 hover:to-slate-700 active:scale-[0.98]">
                  <span>Get Premium</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 0.25s ease, opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .relative {
  transform: scale(0.95) translateY(8px);
}
</style>


