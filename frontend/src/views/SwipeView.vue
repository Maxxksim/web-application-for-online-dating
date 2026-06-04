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
const isRefreshing = ref(false)

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

const refreshDeck = async () => {
  isRefreshing.value = true
  await loadDeck()
  isRefreshing.value = false
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

const closePremiumModal = () => { showPremiumModal.value = false }

const goPremium = async () => {
  showPremiumModal.value = false
  await subscriptionStore.startCheckout()
}

const goToProfile = () => router.push({ name: 'profile' })
const goToFilters = () => router.push({ name: 'search-settings' })

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
  <div class="relative min-h-screen flex flex-col items-center justify-center px-4 pb-32 md:pb-24 pt-4 md:pt-6">
    <div class="flex flex-col items-center gap-3 w-full">

      <div v-if="locationError" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-amber-200/80 bg-amber-50/80 text-[0.72rem] font-semibold text-amber-700">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
        </svg>
        {{ locationError }}
      </div>

      <div v-if="deckError" class="max-w-[380px] p-6 text-center rounded-[28px] border border-slate-200/75 bg-white/72 shadow-md backdrop-blur-xl animate-fade-in-up">
        <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <p class="font-semibold text-[1rem] text-slate-900 m-0 mb-1.5">Action needed</p>
        <p class="text-[0.87rem] text-slate-600 m-0">{{ deckError.message }}</p>
        <div v-if="missingFieldsLabel.length" class="text-[0.82rem] text-slate-500 mt-1.5">
          Missing: {{ missingFieldsLabel.join(', ') }}
        </div>
        <div class="flex flex-col gap-2 mt-4">
          <BaseButton variant="primary" full @click="goToProfile">Complete Profile</BaseButton>
          <BaseButton v-if="deckError.status === 403" variant="secondary" full @click="enableProfile">
            Enable Profile
          </BaseButton>
        </div>
      </div>

      <div v-else-if="isLoading" class="flex flex-col items-center gap-6">
        <div class="w-[min(92vw,420px)] rounded-[24px] bg-slate-200/50 border border-slate-200/50 animate-pulse" style="height: clamp(440px, calc(100svh - 260px), 620px);" />
        <div class="flex flex-col items-center gap-3">
          <div class="flex gap-1.5">
            <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
          </div>
          <p class="text-[0.82rem] font-medium text-slate-500 animate-pulse">Searching for people nearby...</p>
        </div>
      </div>

      <div v-else-if="currentProfile" class="flex flex-col items-center gap-4 w-full animate-fade-in-up">
        <SwipeCard ref="cardRef" :profile="currentProfile" @swiped="handleSwipe" />
        <ActionButtons :show-undo="canUndo" @action="handleAction" @undo="handleUndo" />
      </div>

      <div v-else class="max-w-[380px] w-full animate-fade-in-up">
        <div class="rounded-[28px] border border-slate-200/60 bg-white/75 backdrop-blur-xl shadow-md overflow-hidden">
          <div class="relative h-48 bg-gradient-to-br from-cyan-400/20 via-blue-300/15 to-pink-300/20 flex items-center justify-center">
            <div class="absolute inset-0 flex items-center justify-center gap-3 opacity-60">
              <div class="w-16 h-20 rounded-2xl bg-gradient-to-b from-pink-300/60 to-pink-400/40 border border-pink-300/40 -rotate-6 shadow-sm" />
              <div class="w-16 h-20 rounded-2xl bg-gradient-to-b from-cyan-300/60 to-cyan-400/40 border border-cyan-300/40 rotate-3 shadow-sm -mt-3" />
              <div class="w-16 h-20 rounded-2xl bg-gradient-to-b from-purple-300/60 to-purple-400/40 border border-purple-300/40 -rotate-2 shadow-sm" />
            </div>
            <div class="relative z-10 w-14 h-14 rounded-full bg-white/90 shadow-lg flex items-center justify-center">
              <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
              </svg>
            </div>
          </div>

          <div class="p-6 flex flex-col gap-3 text-center">
            <div>
              <p class="font-bold text-[1.05rem] text-slate-900 m-0">No more profiles</p>
              <p class="text-[0.85rem] text-slate-500 m-0 mt-1">You've seen everyone nearby. Try changing your filters or search again.</p>
            </div>

            <div class="flex flex-col gap-2 mt-1">
              <BaseButton variant="primary" full :loading="isRefreshing" @click="refreshDeck">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Search Again
              </BaseButton>
              <BaseButton variant="secondary" full @click="goToFilters">
                Change Filters
              </BaseButton>
              <BaseButton v-if="canUndo" variant="outline" full @click="handleUndo">
                Undo Last Swipe
              </BaseButton>
            </div>
          </div>
        </div>
      </div>

    </div>

    <MatchModal v-if="isMatch" :match-data="matchData" @close="hideMatch" />

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showPremiumModal" class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-4 sm:p-6">
          <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closePremiumModal"></div>

          <div class="relative w-full max-w-[340px] rounded-[32px] border border-white/60 bg-white/90 p-6 shadow-2xl backdrop-blur-2xl animate-fade-in-up overflow-hidden mb-8 sm:mb-0">
            <div class="pointer-events-none absolute -top-16 -right-16 h-32 w-32 rounded-full bg-gradient-to-bl from-amber-400/30 to-yellow-500/10 blur-2xl"></div>

            <button @click="closePremiumModal" class="absolute top-4 right-4 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100/80 text-slate-500 hover:bg-slate-200 transition">
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
                <button @click="goPremium" :disabled="subscriptionStore.isLoading" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 py-3.5 px-4 text-[0.9rem] font-bold text-white shadow-md hover:from-slate-800 hover:to-slate-700 active:scale-[0.98] transition">
                  Get Premium
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
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .relative {
  transform: scale(0.95) translateY(8px);
}
</style>