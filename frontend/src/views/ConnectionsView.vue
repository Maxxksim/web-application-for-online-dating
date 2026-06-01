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

const profilesMap = ref({})
const previewProfile = ref(null)
const previewLoading = ref(false)
const previewActionLoading = ref(false)

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
        // ignore
      }
    })
  )
}

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

  previewLoading.value = true
  previewProfile.value = null

  try {
    const { data } = await profilesApi.getById(profileId)
    previewProfile.value = { ...data?.profile, ...item, profile_id: profileId }
  } catch {
    previewProfile.value = { ...item, profile_id: profileId }
  } finally {
    previewLoading.value = false
  }
}

const closePreview = () => {
  if (previewActionLoading.value) return
  previewProfile.value = null
}

const tabBadgeClass = 'absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm'
const unreadCardClass = (kind) => kind === 'match'
  ? 'border-violet-500/45 bg-violet-500/10 shadow-md'
  : 'border-sky-500/45 bg-sky-500/10 shadow-md'

const refreshAll = async () => {
  await Promise.allSettled([loadLikes(), loadMatches()])
}

const handlePreviewAction = async (isLiked) => {
  if (!previewProfile.value || previewActionLoading.value) return

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
  const userId = getMatchUserId(match)
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

    <!-- Likes -->
    <section v-if="activeTab === 'likes'" class="glass-panel p-[18px]">
      <div v-if="hasLoadedLikes && !likes.length" class="text-[0.85rem] text-slate-500">No likes yet.</div>
      <div v-else class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-[14px]">
        <button
          v-for="(like, i) in likes"
          :key="like.profile_id || like.id || like.user_id || like.userId || i"
          type="button"
          class="relative flex items-center gap-3 px-4 py-3.5 rounded-2xl border w-full text-left cursor-pointer transition-all duration-[160ms] hover:-translate-y-0.5 hover:shadow-md"
          :class="isUnreadLike(like) ? unreadCardClass('like') : 'border-slate-200/50 bg-white/50 hover:border-sky-500/45 hover:bg-sky-500/10'"
          @click="openProfilePreview(like, 'like')"
        >
          <span v-if="isUnreadLike(like)" class="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-rose-500 shadow-sm"></span>
          <div class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-sky-500/15 to-sky-500/5 text-cyan-500 font-bold shrink-0 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.2)]">
            <img v-if="like.photos?.[0]?.url" :src="like.photos[0].url" alt="Profile" class="w-full h-full object-cover" />
            <span v-else>{{ like.name?.[0] || 'M' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold m-0 mb-0.5 text-[0.9rem]">
              {{ like.name || 'Someone' }}
              <span v-if="like.age" class="ml-1 font-normal text-slate-500">{{ like.age }}</span>
            </p>
            <p class="text-[0.78rem] text-slate-500 m-0">{{ like.city || 'Nearby' }}</p>
          </div>
        </button>
      </div>
    </section>

    <!-- Matches -->
    <section v-else-if="activeTab === 'matches'" class="glass-panel p-[18px]">
      <div v-if="hasLoadedMatches && !matches.length" class="text-[0.85rem] text-slate-500">No matches yet.</div>
      <div v-else class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-[14px]">
        <div
          v-for="(match, i) in matches"
          :key="match.id || match.user_id || match.userId || i"
          class="relative flex items-center gap-3 px-4 py-3.5 rounded-2xl border cursor-pointer transition-all duration-[160ms] hover:-translate-y-0.5 hover:shadow-md"
          :class="isUnreadMatch(match) ? unreadCardClass('match') : 'border-slate-200/50 bg-white/50 hover:border-violet-500/45 hover:bg-violet-500/10'"
          @click="openProfilePreview(match, 'match')"
        >
          <span v-if="isUnreadMatch(match)" class="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-rose-500 shadow-sm"></span>
          <div class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-sky-500/15 to-sky-500/5 text-cyan-500 font-bold shrink-0 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.2)]">
            <img v-if="match.photos?.[0]?.url" :src="match.photos[0].url" alt="Profile" class="w-full h-full object-cover" />
            <span v-else>{{ match.name?.[0] || 'M' }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold m-0 mb-0.5 text-[0.9rem]">
              {{ match.name || 'New match' }}
              <span v-if="match.age" class="ml-1 font-normal text-slate-500">{{ match.age }}</span>
            </p>
            <p class="text-[0.78rem] text-slate-500 m-0">{{ match.city || 'Nearby' }}</p>
          </div>
          <BaseButton size="sm" variant="secondary" :disabled="!getMatchUserId(match)" @click.stop="startChat(match)">
            Chat
          </BaseButton>
          <BaseButton size="sm" variant="primary" :disabled="!getProfileId(match)" @click.stop="openProfilePreview(match, 'match')">
            View
          </BaseButton>
        </div>
      </div>
    </section>

    <!-- Preview modal -->
    <transition name="preview-pop">
      <div v-if="previewProfile" class="fixed inset-0 z-[220] bg-black/30 backdrop-blur-[6px] flex items-center justify-center p-4" @click.self="closePreview">
        <div class="preview-shell glass-panel relative w-full max-w-[380px] sm:max-w-[360px] max-h-[78svh] sm:max-h-[580px] overflow-hidden flex flex-col">
          <button class="absolute top-2 right-2 w-[30px] h-[30px] border-none rounded-full bg-black/50 text-white text-[20px] leading-none cursor-pointer z-10" type="button" @click="closePreview">×</button>

          <div v-if="previewLoading" class="min-h-[240px] flex items-center justify-center text-slate-500">Loading profile...</div>

          <template v-else>
            <div class="min-h-[200px] max-h-[230px] bg-gradient-to-br from-sky-500/20 to-sky-500/5">
              <img v-if="previewProfile.photos?.[0]?.url" :src="previewProfile.photos[0].url" :alt="previewProfile.name || 'Profile'" class="w-full h-full object-cover block" />
              <div v-else class="w-full h-full flex items-center justify-center text-[2.5rem] font-bold text-cyan-500 bg-cyan-50">
                {{ previewProfile.name?.[0] || 'M' }}
              </div>
            </div>

            <div class="px-4 pt-3.5 pb-2">
              <div class="flex items-baseline gap-1.5 mb-0.5">
                <h2 class="text-[1.3rem] font-bold m-0 text-slate-900">{{ previewProfile.name || 'Someone' }}</h2>
                <span v-if="previewProfile.age" class="text-[1rem] text-slate-700">{{ previewProfile.age }}</span>
              </div>
              <p class="text-[0.78rem] text-slate-500 m-0 mb-2">{{ previewProfile.city || 'Nearby' }}</p>
              <p class="text-[0.88rem] text-slate-700 leading-snug m-0 line-clamp-4">
                {{ previewProfile.description || previewProfile.bio || 'No bio provided.' }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-2 px-4 pt-3 pb-4">
              <BaseButton variant="secondary" full :disabled="previewActionLoading" @click="handlePreviewAction(false)">
                Pass
              </BaseButton>
              <BaseButton variant="primary" full :disabled="previewActionLoading" @click="handlePreviewAction(true)">
                Like
              </BaseButton>
            </div>
          </template>
        </div>
      </div>
    </transition>
  </div>
</template>

