<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { likesApi } from '@/api/likes.js'
import { matchesApi } from '@/api/matches.js'
import { chatsApi } from '@/api/chats.js'
import { profilesApi } from '@/api/profiles.js'
import { swipesApi } from '@/api/swipes.js'
import { useToast } from '@/composables/useToast.js'
import { useProfileStore } from '@/stores/profile.js'
import { useNotificationsStore } from '@/stores/notifications.js'
import BaseButton from '@/components/BaseButton.vue'
import ActionButtons from '@/components/ActionButtons.vue'
import { formatLabel } from '@/constants/profileOptions.js'

const router = useRouter()
const { toast } = useToast()
const profileStore = useProfileStore()
const notificationsStore = useNotificationsStore()

const activeTab = ref('likes')

const likes = ref([])
const isLoadingLikes = ref(false)
const hasLoadedLikes = ref(false)

const matches = ref([])
const isLoadingMatches = ref(false)
const hasLoadedMatches = ref(false)

const previewProfile = ref(null)
const previewKind = ref('like')
const previewLoading = ref(false)
const previewActionLoading = ref(false)
const previewPhotoIndex = ref(0)
const previewExpanded = ref(false)

const getProfileId = (p) => p?.profile_id || p?.id || p?.profileId || null
const getProfileUserId = (p) => p?.user_id || p?.userId || p?.user?.id || null
const getMatchUserId = (m) => m?.user_id || m?.userId || m?.user?.id || null

const isUnreadLike = (like) => !!notificationsStore.findUnreadProfileNotification('LikeNotification', getProfileId(like))
const isUnreadMatch = (match) => !!notificationsStore.findUnreadProfileNotification('MatchNotification', getProfileId(match))

const loadLikes = async () => {
  isLoadingLikes.value = true
  try {
    const { data } = await likesApi.getAll()

    likes.value = Array.isArray(data.likes) ? data.likes : []
    await hydratePhotos(likes.value)
  } catch {
    toast.error('Unable to load likes.')
  } finally {
    isLoadingLikes.value = false
    hasLoadedLikes.value = true
  }
}

const hydratePhotos = async (list) => {
  const targets = list.filter((item) => !item.photos?.length)
  await Promise.allSettled(
    targets.map(async (item) => {
      const profileId = getProfileId(item)
      if (!profileId) return
      try {
        const { data } = await profilesApi.getById(profileId)
        if (data?.profile?.photos) item.photos = data.profile.photos
      } catch {
        
      }
    })
  )
}


watch(() => notificationsStore.lastLikeEvent, async (val) => {

  await loadLikes()
})

watch(() => notificationsStore.lastMatchEvent, async () => {
  await loadMatches()
})

const loadMatches = async () => {
  isLoadingMatches.value = true
  try {
    const { data } = await matchesApi.getAll()
    matches.value = Array.isArray(data.matches) ? data.matches : []
    await hydratePhotos(matches.value)
  } catch {
    toast.error('Unable to load matches.')
  } finally {
    isLoadingMatches.value = false
    hasLoadedMatches.value = true
  }
}

const openProfilePreview = async (item, kind = 'like') => {
  const profileId = getProfileId(item)
  if (!profileId) {
    toast.error('This profile cannot be opened right now.')
    return
  }

  const typeName = kind === 'match' ? 'MatchNotification' : 'LikeNotification'
  await notificationsStore.markProfileNotificationAsRead(typeName, profileId)

  previewKind.value = kind
  previewPhotoIndex.value = 0
  previewExpanded.value = false
  previewLoading.value = true
  previewProfile.value = { ...item, profile_id: profileId }

  try {
    const { data } = await profilesApi.getById(profileId)
    const fullProfile = data?.profile || {}
    previewProfile.value = {
      ...item,
      ...fullProfile,
      profile_id: profileId,
      user_id: getProfileUserId(fullProfile) || getProfileUserId(item),
    }
  } catch {
    previewProfile.value = { ...item, profile_id: profileId }
  } finally {
    previewLoading.value = false
  }
}

const closePreview = () => {
  if (previewActionLoading.value) return
  previewProfile.value = null
  previewExpanded.value = false
}

const tabBadgeClass = 'absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm'
const unreadCardClass = (kind) => kind === 'match'
  ? 'border-violet-500/45 bg-violet-500/10 shadow-md'
  : 'border-sky-500/45 bg-sky-500/10 shadow-md'

const previewPhotos = computed(() =>
  previewProfile.value?.photos?.filter((photo) => photo?.url) || []
)

const currentPreviewPhoto = computed(() =>
  previewPhotos.value[previewPhotoIndex.value]?.url
  || previewProfile.value?.photo_url
  || null
)

const previewInterests = computed(() => {
  const interests = previewProfile.value?.interests
  return Array.isArray(interests) ? interests : []
})

const previewDetails = computed(() => {
  const profile = previewProfile.value || {}
  return [
    profile.height ? { label: 'Height', value: `${profile.height} cm` } : null,
    profile.weight ? { label: 'Weight', value: `${profile.weight} kg` } : null,
    profile.body_type ? { label: 'Body', value: formatLabel(profile.body_type) } : null,
    profile.eye_color ? { label: 'Eyes', value: formatLabel(profile.eye_color) } : null,
    profile.hair_color ? { label: 'Hair', value: formatLabel(profile.hair_color) } : null,
    profile.zodiac_sign ? { label: 'Zodiac', value: formatLabel(profile.zodiac_sign) } : null,
    profile.children ? { label: 'Children', value: formatLabel(profile.children) } : null,
    profile.smoking ? { label: 'Smoking', value: formatLabel(profile.smoking) } : null,
    profile.drinking ? { label: 'Drinking', value: formatLabel(profile.drinking) } : null,
    profile.exercise ? { label: 'Exercise', value: formatLabel(profile.exercise) } : null,
    profile.dating_purpose ? { label: 'Looking for', value: formatLabel(profile.dating_purpose) } : null,
  ].filter(Boolean)
})

const nextPreviewPhoto = () => {
  if (previewPhotos.value.length > 1) {
    previewPhotoIndex.value = (previewPhotoIndex.value + 1) % previewPhotos.value.length
  }
}

const prevPreviewPhoto = () => {
  if (previewPhotos.value.length > 1) {
    previewPhotoIndex.value = (previewPhotoIndex.value - 1 + previewPhotos.value.length) % previewPhotos.value.length
  }
}

const handlePreviewPhotoClick = (event) => {
  if (previewPhotos.value.length <= 1) return
  const rect = event.currentTarget.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  if (clickX < rect.width * 0.33) {
    prevPreviewPhoto()
  } else {
    nextPreviewPhoto()
  }
}

const refreshAll = async () => {
  await Promise.allSettled([loadLikes(), loadMatches()])
}

const handlePreviewAction = async (isLiked) => {
  if (previewKind.value === 'match' || !previewProfile.value || previewActionLoading.value) return

  const targetId = getProfileUserId(previewProfile.value)
  const swipedId = Number(targetId)
  if (!Number.isInteger(swipedId) || swipedId <= 0) {
    toast.error('Unable to act on this profile.')
    return
  }

  previewActionLoading.value = true
  try {
    await swipesApi.swipe(swipedId, isLiked)
    previewProfile.value = null
    await refreshAll()
    toast.success(isLiked ? 'Like sent.' : 'Passed.')
  } catch {
    toast.error('Unable to save your choice.')
  } finally {
    previewActionLoading.value = false
  }
}

const startChat = async (match) => {
  const userId = getMatchUserId(match) || getProfileUserId(match)
  if (!userId) {
    toast.error('Chat is not available for this match yet.')
    return
  }

  try {
    const { data } = await chatsApi.getAll()
    const chatsList = Array.isArray(data.chats) ? data.chats : []
    const currentId = profileStore.myProfile?.user_id
    const existing = chatsList.find((chat) => {
      const other = chat?.users?.find((u) => u?.id !== currentId)
      return other?.id === userId
    })

    const profileId = getProfileId(match)
    const query = profileId
      ? { user: userId, profile: profileId }
      : { user: userId }

    if (existing?.id) {
      router.push({ name: 'chats', query: { chat: existing.id } })
      return
    }

    router.push({ name: 'chats', query })
  } catch {
    toast.error('Unable to open chat.')
  }
}

onMounted(async () => {
  if (!profileStore.myProfile) await profileStore.fetchMyProfile()
  await Promise.allSettled([loadLikes(), loadMatches()])
})
</script>

<template>
  <div class="page px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap gap-[10px] mb-6 max-w-6xl mx-auto">
      <button class="relative px-[18px] py-[8px] rounded-full border border-slate-200 bg-white/85 text-slate-700 text-[0.85rem] font-semibold cursor-pointer shadow-sm transition-all duration-[160ms]" :class="activeTab === 'likes' ? 'text-cyan-500 border-sky-500/45 bg-gradient-to-br from-sky-500/15 to-sky-500/5 shadow-md' : ''" @click="activeTab = 'likes'">
        Likes
        <span v-if="notificationsStore.likeCount" :class="tabBadgeClass">
          {{ notificationsStore.likeCount > 9 ? '9+' : notificationsStore.likeCount }}
        </span>
      </button>
      <button class="relative px-[18px] py-[8px] rounded-full border border-slate-200 bg-white/85 text-slate-700 text-[0.85rem] font-semibold cursor-pointer shadow-sm transition-all duration-[160ms]" :class="activeTab === 'matches' ? 'text-cyan-500 border-sky-500/45 bg-gradient-to-br from-sky-500/15 to-sky-500/5 shadow-md' : ''" @click="activeTab = 'matches'">
        Matches
        <span v-if="notificationsStore.matchCount" :class="tabBadgeClass">
          {{ notificationsStore.matchCount > 9 ? '9+' : notificationsStore.matchCount }}
        </span>
      </button>
    </div>

    <section v-if="activeTab === 'likes'" class="glass-panel p-[18px]">
      <div v-if="hasLoadedLikes && !likes.length" class="text-[0.85rem] text-slate-500">No likes yet.</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-[14px]">
        <button
          v-for="(like, i) in likes"
          :key="like.profile_id || like.id || like.user_id || like.userId || i"
          type="button"
          class="connection-card relative flex items-center gap-4 px-4 py-4 rounded-2xl border w-full text-left cursor-pointer transition-all duration-[160ms] hover:-translate-y-0.5 hover:shadow-md"
          :class="isUnreadLike(like) ? unreadCardClass('like') : 'border-slate-200/50 bg-white/50 hover:border-sky-500/45 hover:bg-sky-500/10'"
          @click="openProfilePreview(like, 'like')"
        >
          <span v-if="isUnreadLike(like)" class="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-rose-500 shadow-sm"></span>
          <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-sky-500/15 to-sky-500/5 text-cyan-500 font-bold shrink-0 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.2)]">
            <img v-if="like.photos?.[0]?.url" :src="like.photos[0].url" alt="Profile" class="w-full h-full object-cover" />
            <span v-else class="text-lg">{{ like.name?.[0] || 'M' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold m-0 mb-0.5 text-[0.92rem] truncate">
              {{ like.name || 'Someone' }}
              <span v-if="like.age" class="ml-1 font-normal text-slate-500">{{ like.age }}</span>
            </p>
            <p class="text-[0.78rem] text-slate-500 m-0 truncate">{{ [like.city, like.country].filter(Boolean).join(', ') || 'Nearby' }}</p>
          </div>
        </button>
      </div>
    </section>

    <section v-else-if="activeTab === 'matches'" class="glass-panel p-[18px]">
      <div v-if="hasLoadedMatches && !matches.length" class="text-[0.85rem] text-slate-500">No matches yet.</div>
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-[14px]">
        <div
          v-for="(match, i) in matches"
          :key="match.id || match.user_id || match.userId || i"
          class="connection-card relative flex flex-col rounded-2xl border cursor-pointer transition-all duration-[160ms] hover:-translate-y-0.5 hover:shadow-md"
          :class="isUnreadMatch(match) ? unreadCardClass('match') : 'border-slate-200/50 bg-white/50 hover:border-violet-500/45 hover:bg-violet-500/10'"
          @click="openProfilePreview(match, 'match')"
        >
          <span v-if="isUnreadMatch(match)" class="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-rose-500 shadow-sm z-10"></span>
          <div class="flex items-center gap-4 px-4 pt-4 pb-3">
            <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-violet-500/15 to-violet-500/5 text-violet-500 font-bold shrink-0 shadow-[inset_0_0_0_1px_rgba(139,92,246,0.2)]">
              <img v-if="match.photos?.[0]?.url" :src="match.photos[0].url" alt="Profile" class="w-full h-full object-cover" />
              <span v-else class="text-lg">{{ match.name?.[0] || 'M' }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold m-0 mb-0.5 text-[0.92rem] truncate">
                {{ match.name || 'New match' }}
                <span v-if="match.age" class="ml-1 font-normal text-slate-500">{{ match.age }}</span>
              </p>
              <p class="text-[0.78rem] text-slate-500 m-0 truncate">{{ [match.city, match.country].filter(Boolean).join(', ') || 'Nearby' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2.5 px-4 pb-4">
            <BaseButton size="sm" variant="secondary" class="flex-1" :disabled="!getMatchUserId(match) && !getProfileUserId(match)" @click.stop="startChat(match)">
              Chat
            </BaseButton>
            <BaseButton size="sm" variant="primary" class="flex-1" :disabled="!getProfileId(match)" @click.stop="openProfilePreview(match, 'match')">
              View
            </BaseButton>
          </div>
        </div>
      </div>
    </section>

    <Teleport to="body">
      <transition name="preview-pop">
        <div v-if="previewProfile" class="fixed inset-0 z-[9999] bg-black/35 backdrop-blur-[6px] flex items-center justify-center px-4 pb-28 pt-4 sm:pb-24 sm:pt-6" @click.self="closePreview">
          <div class="preview-shell relative flex h-[min(76svh,650px)] w-full max-w-[410px] overflow-hidden rounded-[28px] border border-white/50 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.28)] sm:h-[min(86svh,720px)] md:h-[min(82svh,680px)] md:max-w-[880px] md:gap-5 md:overflow-visible md:border-0 md:bg-transparent md:shadow-none">
            <button class="absolute right-4 top-4 z-30 flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-black/45 text-white shadow-lg backdrop-blur-md transition hover:bg-black/65" type="button" aria-label="Close profile preview" @click="closePreview">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>

            <div v-if="previewLoading" class="min-h-[240px] flex items-center justify-center text-slate-500">Loading profile...</div>

            <template v-else>
              <div class="hidden h-full w-full grid-cols-[1fr_1fr] gap-5 md:grid">
                <div class="relative overflow-hidden rounded-[30px] border border-white/45 bg-slate-950 shadow-[0_24px_70px_rgba(15,23,42,0.28)] cursor-pointer" @click="handlePreviewPhotoClick">
                  <img v-if="currentPreviewPhoto" :src="currentPreviewPhoto" :alt="previewProfile.name || 'Profile'" class="h-full w-full object-cover object-top select-none" style="-webkit-user-drag: none;" />
                  <div v-else class="flex h-full w-full items-center justify-center bg-cyan-50 text-[3rem] font-bold text-cyan-500">
                    {{ previewProfile.name?.[0] || 'M' }}
                  </div>

                  <div v-if="previewPhotos.length > 1" class="absolute left-4 right-4 top-4 z-20 flex gap-1.5 pointer-events-none">
                    <div
                      v-for="(_, i) in previewPhotos"
                      :key="i"
                      class="h-1 flex-1 rounded-full transition-all duration-300"
                      :class="i === previewPhotoIndex ? 'bg-white shadow-[0_0_4px_rgba(255,255,255,0.5)]' : 'bg-white/35'"
                    />
                  </div>

                  <template v-if="previewPhotos.length > 1">
                    <button type="button" class="no-lift absolute left-4 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition-colors hover:bg-black/50" aria-label="Previous photo" @click.stop="prevPreviewPhoto">
                      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                      </svg>
                    </button>
                    <button type="button" class="no-lift absolute right-4 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition-colors hover:bg-black/50" aria-label="Next photo" @click.stop="nextPreviewPhoto">
                      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6" />
                      </svg>
                    </button>
                  </template>
                </div>

                <div class="relative flex min-h-0 flex-col overflow-hidden rounded-[30px] border border-white/30 bg-[linear-gradient(145deg,#1e293b_0%,#0891b2_48%,#db2777_100%)] p-6 text-white shadow-[0_24px_70px_rgba(15,23,42,0.28)]">
                  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_12%,rgba(255,255,255,0.25),transparent_28%),radial-gradient(circle_at_85%_90%,rgba(255,255,255,0.15),transparent_28%)]"></div>
                  <div class="relative z-10 flex min-h-0 flex-1 flex-col">
                    <div class="mb-4 pr-10">
                      <div class="flex items-end gap-2">
                        <h2 class="m-0 text-[2.25rem] font-extrabold leading-none">{{ previewProfile.name || 'Someone' }}</h2>
                        <span v-if="previewProfile.age" class="text-[1.55rem] font-medium leading-none text-white/90">{{ previewProfile.age }}</span>
                      </div>
                      <div class="mt-3 flex flex-wrap items-center gap-2 text-[0.85rem] font-semibold text-white/90">
                        <span>{{ [previewProfile.city, previewProfile.country].filter(Boolean).join(', ') || 'Nearby' }}</span>
                        <span v-if="previewProfile.distance !== undefined && previewProfile.distance !== null" class="rounded-full bg-white/20 px-2.5 py-1 backdrop-blur shadow-sm">
                          {{ Math.round(previewProfile.distance) }} km away
                        </span>
                        <span v-if="previewKind === 'match'" class="rounded-full bg-white/20 px-2.5 py-1 backdrop-blur shadow-sm">
                          Matched
                        </span>
                      </div>
                    </div>

                    <div class="thin-scroll min-h-0 flex-1 overflow-y-auto pr-1">
                      <div class="rounded-[22px] border border-white/15 bg-white/10 p-4 backdrop-blur-md shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]">
                        <p class="m-0 text-[0.95rem] leading-relaxed text-white">
                          {{ previewProfile.description || previewProfile.bio || 'No bio provided.' }}
                        </p>
                      </div>

                      <div v-if="previewDetails.length" class="mt-4 grid grid-cols-2 gap-2">
                        <div v-for="detail in previewDetails" :key="detail.label" class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-md">
                          <p class="m-0 text-[0.66rem] font-semibold uppercase text-white/70">{{ detail.label }}</p>
                          <p class="m-0 mt-0.5 text-[0.86rem] font-semibold text-white">{{ detail.value }}</p>
                        </div>
                      </div>

                      <div v-if="previewInterests.length" class="mt-4 flex flex-wrap gap-2">
                        <span
                          v-for="interest in previewInterests"
                          :key="interest.id || interest.interest || interest"
                          class="rounded-full border border-white/15 bg-white/14 px-3 py-1.5 text-[0.75rem] font-semibold text-white backdrop-blur-md"
                        >
                          {{ formatLabel(interest.interest || interest) }}
                        </span>
                      </div>
                    </div>

                    <div v-if="previewKind === 'match'" class="mt-5">
                      <BaseButton variant="primary" full :disabled="!getMatchUserId(previewProfile) && !getProfileUserId(previewProfile)" @click="startChat(previewProfile)">
                        Start chat
                      </BaseButton>
                    </div>
                    <div v-else class="mt-5 flex items-center justify-center">
                      <ActionButtons :disabled="previewActionLoading" @like="handlePreviewAction(true)" @dislike="handlePreviewAction(false)" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="relative flex min-h-0 flex-1 flex-col bg-slate-950 md:hidden">
                <div v-if="previewPhotos.length > 1" class="absolute inset-0 z-10 flex">
                  <div class="w-1/3 h-full cursor-pointer" @click.stop="prevPreviewPhoto"></div>
                  <div class="flex-1 h-full"></div>
                  <div class="w-1/3 h-full cursor-pointer" @click.stop="nextPreviewPhoto"></div>
                </div>

                <img v-if="currentPreviewPhoto" :src="currentPreviewPhoto" :alt="previewProfile.name || 'Profile'" class="w-full h-full object-cover object-top block select-none" style="-webkit-user-drag: none;" />
                <div v-else class="w-full h-full flex items-center justify-center text-[2.5rem] font-bold text-cyan-500 bg-cyan-50">
                  {{ previewProfile.name?.[0] || 'M' }}
                </div>

                <div v-if="previewPhotos.length > 1" class="absolute left-4 right-4 top-4 flex gap-1.5 z-20 pointer-events-none">
                  <div
                    v-for="(_, i) in previewPhotos"
                    :key="i"
                    class="h-[3px] flex-1 rounded-full transition-all duration-300"
                    :class="i === previewPhotoIndex ? 'bg-white shadow-[0_0_4px_rgba(255,255,255,0.5)]' : 'bg-white/35'"
                  />
                </div>

                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/88 via-black/28 to-black/8"></div>

                <div class="absolute bottom-0 left-0 right-0 z-20 flex flex-col px-4 pb-[5.5rem] pt-20 text-white pointer-events-none bg-gradient-to-t from-black/90 via-black/40 to-transparent">
                  <div class="pointer-events-auto flex items-end justify-between gap-3">
                    <div class="flex-1 min-w-0 flex flex-col gap-1.5 drop-shadow-md">
                      <div class="flex items-end gap-2.5">
                        <h2 class="text-[2.2rem] font-extrabold m-0 leading-none truncate">{{ previewProfile.name || 'Someone' }}</h2>
                        <span v-if="previewProfile.age" class="text-[1.5rem] font-medium opacity-90 shrink-0">{{ previewProfile.age }}</span>
                      </div>
                      <div class="flex items-center gap-2 text-[0.85rem] opacity-90 font-medium truncate">
                        <span class="truncate">{{ [previewProfile.city, previewProfile.country].filter(Boolean).join(', ') || 'Nearby' }}</span>
                        <span v-if="previewProfile.distance !== undefined && previewProfile.distance !== null" class="rounded-full bg-white/20 px-2.5 py-0.5 backdrop-blur-sm shrink-0">
                          {{ Math.round(previewProfile.distance) }} km away
                        </span>
                      </div>
                    </div>
                    <button @click.stop="previewExpanded = true" class="w-10 h-10 shrink-0 bg-white text-emerald-500 rounded-full flex items-center justify-center shadow-[0_4px_12px_rgba(0,0,0,0.3)] transition-transform hover:scale-105 active:scale-95" aria-label="Open profile">
                      <svg class="w-5 h-5 translate-y-[1px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <Teleport to="body">
                <transition name="slide-up">
                  <div v-if="previewExpanded" @touchmove.stop class="fixed inset-0 z-[10000] bg-slate-950 overflow-y-auto thin-scroll md:hidden">
                    <div class="relative w-full h-[65svh] shrink-0">
                      <img :src="currentPreviewPhoto" class="w-full h-full object-cover object-top select-none" style="-webkit-user-drag: none;" />
                      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent pointer-events-none"></div>
                      
                      <button class="absolute top-5 right-5 z-30 flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white shadow-md backdrop-blur-md transition-transform hover:scale-105 active:scale-95" @click.stop="previewExpanded = false">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                      </button>

                      <div class="absolute inset-0 z-10 flex">
                         <div class="w-1/3 h-full cursor-pointer" @click="prevPreviewPhoto"></div>
                         <div class="flex-1 h-full"></div>
                         <div class="w-1/3 h-full cursor-pointer" @click="nextPreviewPhoto"></div>
                      </div>

                      <div v-if="previewPhotos.length > 1" class="absolute top-4 left-4 right-20 flex gap-1.5 z-20 pointer-events-none">
                        <div v-for="(_, i) in previewPhotos" :key="i" class="h-[3px] flex-1 rounded-full transition-all duration-300" :class="i === previewPhotoIndex ? 'bg-white shadow-[0_0_4px_rgba(255,255,255,0.5)]' : 'bg-white/35'"></div>
                      </div>
                    </div>

                    <div class="px-5 pt-3 pb-28 text-white relative z-20">
                      <div class="flex justify-between items-start mb-6">
                        <div class="flex-1 min-w-0">
                          <div class="flex items-end gap-3 mb-1">
                            <h2 class="text-[2.2rem] font-extrabold leading-none truncate">{{ previewProfile.name || 'Someone' }}</h2>
                            <span v-if="previewProfile.age" class="text-[1.5rem] font-medium text-white/90 shrink-0">{{ previewProfile.age }}</span>
                          </div>
                          <div class="flex flex-wrap items-center gap-2 text-[0.85rem] font-medium text-white/85">
                            <span class="truncate">{{ [previewProfile.city, previewProfile.country].filter(Boolean).join(', ') || 'Nearby' }}</span>
                            <span v-if="previewProfile.distance !== undefined && previewProfile.distance !== null" class="rounded-full bg-white/10 px-2.5 py-0.5">
                              {{ Math.round(previewProfile.distance) }} km away
                            </span>
                          </div>
                        </div>
                      </div>

                      <div v-if="previewProfile.description || previewProfile.bio" class="mb-6">
                        <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">About</h3>
                        <p class="text-[0.95rem] leading-[1.6] text-white/95 m-0 whitespace-pre-line">{{ previewProfile.description || previewProfile.bio }}</p>
                      </div>

                      <div v-if="previewDetails.length" class="mb-6">
                        <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">Details</h3>
                        <div class="flex flex-wrap gap-2">
                          <span v-for="detail in previewDetails" :key="detail.label" class="rounded-[14px] border border-white/10 bg-white/5 px-3 py-2 text-[0.8rem] text-white/90 font-medium backdrop-blur-sm">
                            {{ detail.icon || '' }} {{ detail.value }}
                          </span>
                        </div>
                      </div>

                      <div v-if="previewInterests.length" class="mb-6">
                        <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">Interests</h3>
                        <div class="flex flex-wrap gap-2">
                          <span v-for="interest in previewInterests" :key="interest.id || interest.interest || interest" class="rounded-full border border-cyan-400/20 bg-cyan-500/10 px-3 py-1.5 text-[0.8rem] text-white/90 font-medium backdrop-blur-sm">
                            {{ formatLabel(interest.interest || interest) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition>
              </Teleport>

              <div v-if="previewKind === 'match'" class="absolute bottom-4 left-5 right-5 z-50 md:hidden">
                <BaseButton variant="primary" full :disabled="!getMatchUserId(previewProfile) && !getProfileUserId(previewProfile)" @click="startChat(previewProfile)">
                  Start chat
                </BaseButton>
              </div>
              <div v-else class="absolute bottom-6 left-0 right-0 z-50 flex items-center justify-center w-full px-5 md:hidden pointer-events-none">
                <div class="pointer-events-auto flex justify-center w-full max-w-[300px]">
                  <ActionButtons :disabled="previewActionLoading" @like="handlePreviewAction(true)" @dislike="handlePreviewAction(false)" />
                </div>
              </div>
            </template>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<style scoped>
.connection-card {
  min-height: 0;
}

/* ── preview popup ── */
.preview-pop-enter-active,
.preview-pop-leave-active {
  transition: opacity 220ms ease;
}
.preview-pop-enter-from,
.preview-pop-leave-to {
  opacity: 0;
}
.preview-pop-enter-active .preview-shell {
  animation: preview-in 320ms cubic-bezier(0.18, 0.9, 0.2, 1.12) both;
}
@keyframes preview-in {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(14px);
  }
}

.thin-scroll::-webkit-scrollbar {
  width: 4px;
}
.thin-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.thin-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
}
</style>