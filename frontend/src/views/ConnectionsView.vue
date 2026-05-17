<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { likesApi } from '@/api/likes.js'
import { matchesApi } from '@/api/matches.js'
import { chatsApi } from '@/api/chats.js'
import { profilesApi } from '@/api/profiles.js'
import { swipesApi } from '@/api/swipes.js'
import { useToast } from '@/composables/useToast.js'
import { useNotificationsStore } from '@/stores/notifications.js'
import { useProfileStore } from '@/stores/profile.js'
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const { toast } = useToast()
const notificationsStore = useNotificationsStore()
const profileStore = useProfileStore()

const activeTab = ref('likes')

const likes = ref([])
const isLoadingLikes = ref(false)

const matches = ref([])
const isLoadingMatches = ref(false)

const profilesMap = ref({})
const chatProfilesByUserId = ref({})
const previewProfile = ref(null)
const previewLoading = ref(false)
const previewActionLoading = ref(false)

const notifications = computed(() => notificationsStore.notifications)
const isLoadingNotifications = computed(() => notificationsStore.isLoading)

const getProfileId = (profile) => profile?.profile_id || profile?.id || profile?.profileId || null
const getProfileUserId = (profile) => profile?.user_id || profile?.userId || profile?.user?.id || null
const getMatchUserId = (match) => match?.user_id || match?.userId || match?.user?.id || null

const loadLikes = async () => {
  isLoadingLikes.value = true
  try {
    const { data } = await likesApi.getAll()
    likes.value = Array.isArray(data.likes) ? data.likes : []
    await hydrateLikesPhotos()
  } catch (err) {
    toast.error('Unable to load likes.')
  } finally {
    isLoadingLikes.value = false
  }
}

const hydrateLikesPhotos = async () => {
  const targets = likes.value.filter((like) => !like.photos?.length)
  await Promise.allSettled(
    targets.map(async (like) => {
      const profileId = getProfileId(like)
      if (!profileId) return
      try {
        const { data } = await profilesApi.getById(profileId)
        if (data?.profile?.photos) {
          like.photos = data.profile.photos
        }
      } catch (err) {
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
    await hydrateMatchesPhotos()
  } catch (err) {
    toast.error('Unable to load matches.')
  } finally {
    isLoadingMatches.value = false
  }
}

const hydrateMatchesPhotos = async () => {
  const targets = matches.value.filter((match) => !match.photos?.length)
  await Promise.allSettled(
    targets.map(async (match) => {
      const profileId = getProfileId(match)
      if (!profileId) return
      try {
        const { data } = await profilesApi.getById(profileId)
        if (data?.profile?.photos) {
          match.photos = data.profile.photos
        }
      } catch (err) {
        // ignore
      }
    })
  )
}

const openLikePreview = async (like) => {
  const profileId = getProfileId(like)
  if (!profileId) {
    toast.error('This profile cannot be opened right now.')
    return
  }

  previewLoading.value = true
  previewProfile.value = null

  try {
    const { data } = await profilesApi.getById(profileId)
    previewProfile.value = {
      ...(data?.profile || {}),
      ...like,
      profile_id: profileId,
    }
  } catch (err) {
    previewProfile.value = {
      ...like,
      profile_id: profileId,
    }
  } finally {
    previewLoading.value = false
  }
}

const closePreview = () => {
  if (previewActionLoading.value) return
  previewProfile.value = null
}

const refreshAll = async () => {
  await Promise.allSettled([loadLikes(), loadMatches(), refreshNotifications()])
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
  } catch (err) {
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
    const { data } = await chatsApi.firstOrCreate(userId)
    router.push({ name: 'chats', query: { chat: data.chat?.id } })
  } catch (err) {
    toast.error('Unable to start chat.')
  }
}

const getNotificationProfileId = (notification) =>
  notification.data?.matched_user_profile_id || notification.data?.liked_by_user_profile_id

const getNotificationSenderId = (notification) => notification.data?.sender_id

const isMatch = (notification) => notification.type?.includes('MatchNotification')
const isMessage = (notification) => notification.type?.includes('MessageNotification')

const formatRelativeTime = (value) => {
  if (!value) return ''
  const date = new Date(value)
  const diffMs = date.getTime() - Date.now()
  const diffMinutes = Math.round(diffMs / 60000)

  const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
  if (Math.abs(diffMinutes) < 60) return rtf.format(diffMinutes, 'minute')

  const diffHours = Math.round(diffMinutes / 60)
  if (Math.abs(diffHours) < 24) return rtf.format(diffHours, 'hour')

  const diffDays = Math.round(diffHours / 24)
  return rtf.format(diffDays, 'day')
}

const hydrateProfiles = async () => {
  const ids = notifications.value
    .map(getNotificationProfileId)
    .filter(Boolean)
    .filter((id) => !profilesMap.value[id])

  await Promise.allSettled(
    ids.map(async (id) => {
      try {
        const { data } = await profilesApi.getById(id)
        profilesMap.value[id] = data.profile
      } catch (err) {
        // ignore
      }
    })
  )
}

const loadChatProfiles = async () => {
  try {
    const { data } = await chatsApi.getAll()
    const chats = Array.isArray(data.chats) ? data.chats : []
    const map = {}
    chats.forEach((chat) => {
      const userId = chat?.interlocutor_id || chat?.interlocutor?.id
      const profile = chat?.interlocutor?.profile || null
      if (userId && profile) map[userId] = profile
    })
    chatProfilesByUserId.value = map
  } catch (err) {
    // ignore
  }
}

const displayNotifications = computed(() =>
  notifications.value.map((notification) => {
    const profileId = getNotificationProfileId(notification)
    const profile = profileId ? profilesMap.value[profileId] : null
    const senderId = getNotificationSenderId(notification)
    const senderProfile = senderId ? chatProfilesByUserId.value[senderId] : null
    const label = isMatch(notification) ? 'Match' : isMessage(notification) ? 'Message' : 'Like'
    const name = profile?.name || senderProfile?.name || 'someone'

    return {
      id: notification.id,
      label,
      message: isMatch(notification)
        ? `You matched with ${name}.`
        : isMessage(notification)
          ? `New message from ${name}.`
          : `New like from ${name}.`,
      time: formatRelativeTime(notification.created_at),
      profile: profile || senderProfile,
      initial: name?.[0] || 'M',
    }
  })
)

const refreshNotifications = async () => {
  const result = await notificationsStore.fetchAll()
  if (!result?.success) {
    toast.error('Unable to load activity.')
  }
  await hydrateProfiles()

  if (notifications.value.some(isMessage)) {
    await loadChatProfiles()
  }
}

const handleNotificationClick = async (notification) => {
  await notificationsStore.markAsRead(notification.id)
  if (isMessage(notification) && notification.data?.chat_id) {
    router.push({ name: 'chats', query: { chat: notification.data.chat_id } })
  }
}

const markAll = async () => {
  await notificationsStore.markAllAsRead()
}

onMounted(async () => {
  if (!profileStore.myProfile) await profileStore.fetchMyProfile()
  await Promise.allSettled([loadLikes(), loadMatches(), refreshNotifications()])
})

watch(notifications, async () => {
  await hydrateProfiles()
  if (notifications.value.some(isMessage)) {
    await loadChatProfiles()
  }
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <p class="eyebrow">Connections</p>
      <h1 class="page-title">Likes, matches, activity</h1>
    </div>

    <div class="tabs">
      <button class="tab" :class="{ 'tab--active': activeTab === 'likes' }" @click="activeTab = 'likes'">Likes</button>
      <button class="tab" :class="{ 'tab--active': activeTab === 'matches' }" @click="activeTab = 'matches'">Matches</button>
      <button class="tab" :class="{ 'tab--active': activeTab === 'activity' }" @click="activeTab = 'activity'">Activity</button>
    </div>

    <section v-if="activeTab === 'likes'" class="glass-panel p-4 md:p-6">
      <div v-if="isLoadingLikes" class="text-sm text-white/60">Loading likes...</div>
      <div v-else-if="!likes.length" class="text-sm text-white/60">No likes yet.</div>
      <div v-else class="connections-grid">
        <button
          v-for="(like, index) in likes"
          :key="like.profile_id || like.id || like.user_id || like.userId || index"
          type="button"
          class="connection-card connection-card--clickable"
          @click="openLikePreview(like)"
        >
          <div class="connection-card__avatar">
            <img v-if="like.photos?.[0]?.url" :src="like.photos[0].url" alt="Profile" />
            <span v-else>{{ like.name?.[0] || 'M' }}</span>
          </div>
          <div class="connection-card__body">
            <p class="connection-card__name">
              {{ like.name || 'Someone' }}
              <span v-if="like.age" class="connection-card__age">{{ like.age }}</span>
            </p>
            <p class="connection-card__meta">{{ like.city || 'Nearby' }}</p>
          </div>
        </button>
      </div>
    </section>

    <section v-else-if="activeTab === 'matches'" class="glass-panel p-4 md:p-6">
      <div v-if="isLoadingMatches" class="text-sm text-white/60">Loading matches...</div>
      <div v-else-if="!matches.length" class="text-sm text-white/60">No matches yet.</div>
      <div v-else class="connections-grid">
        <div v-for="(match, index) in matches" :key="match.id || match.user_id || match.userId || index" class="connection-card">
          <div class="connection-card__avatar">
            <img v-if="match.photos?.[0]?.url" :src="match.photos[0].url" alt="Profile" />
            <span v-else>{{ match.name?.[0] || 'M' }}</span>
          </div>
          <div class="connection-card__body">
            <p class="connection-card__name">
              {{ match.name || 'New match' }}
              <span v-if="match.age" class="connection-card__age">{{ match.age }}</span>
            </p>
            <p class="connection-card__meta">{{ match.city || 'Nearby' }}</p>
          </div>
          <BaseButton
            size="sm"
            variant="secondary"
            :disabled="!getMatchUserId(match)"
            @click="startChat(match)"
          >
            Chat
          </BaseButton>
        </div>
      </div>
    </section>

    <section v-else class="glass-panel p-4 md:p-6">
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm uppercase tracking-[0.3em] text-white/60">Unread</p>
        <BaseButton v-if="displayNotifications.length" size="sm" variant="ghost" @click="markAll">
          Mark all as read
        </BaseButton>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div v-if="isLoadingNotifications" class="text-sm text-white/60">Loading...</div>
        <ul v-else-if="displayNotifications.length > 0" class="flex flex-col gap-3">
          <li
            v-for="notification in displayNotifications"
            :key="notification.id"
            class="glass-panel glass-panel--tight px-4 py-3 flex items-center gap-3 hover:border-violet-400/60 transition cursor-pointer"
            @click="handleNotificationClick(notification)"
          >
            <div class="w-10 h-10 rounded-full overflow-hidden border border-white/10">
              <img v-if="notification.profile?.photos?.[0]?.url" :src="notification.profile.photos[0].url" alt="Profile" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center bg-white/10 text-sm font-semibold">
                {{ notification.initial }}
              </div>
            </div>
            <span class="chip">{{ notification.label }}</span>
            <div class="flex-1">
              <p class="text-sm font-semibold text-white">{{ notification.message }}</p>
              <p class="text-xs text-white/60">{{ notification.time }}</p>
            </div>
          </li>
        </ul>
        <div v-else class="text-center text-white/60 py-10">
          <p>No activity yet.</p>
        </div>
      </div>
    </section>

    <transition name="preview-pop">
      <div v-if="previewProfile" class="profile-preview-modal" @click.self="closePreview">
        <div class="profile-preview-shell glass-panel">
          <button class="profile-preview-close" type="button" @click="closePreview">×</button>

          <div v-if="previewLoading" class="profile-preview-state text-white/70">
            Loading profile...
          </div>

          <template v-else>
            <div class="profile-preview-photo">
              <img
                v-if="previewProfile.photos?.[0]?.url"
                :src="previewProfile.photos[0].url"
                :alt="previewProfile.name || 'Profile'"
              />
              <div v-else class="profile-preview-photo__fallback">
                {{ previewProfile.name?.[0] || 'M' }}
              </div>
            </div>

            <div class="profile-preview-body">
              <div class="flex items-end gap-2 mb-1">
                <h2 class="text-2xl font-extrabold leading-none">{{ previewProfile.name || 'Someone' }}</h2>
                <span v-if="previewProfile.age" class="text-lg opacity-90">{{ previewProfile.age }}</span>
              </div>
              <p class="text-xs text-white/70 mb-3">{{ previewProfile.city || 'Nearby' }}</p>
              <p class="text-sm leading-5 text-white/90 line-clamp-4">
                {{ previewProfile.description || previewProfile.bio || 'No bio provided.' }}
              </p>
            </div>

            <div class="profile-preview-actions">
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

<style scoped>
.tabs {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.tab {
  padding: 8px 18px;
  border-radius: 999px;
  border: 1px solid var(--border-subtle);
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-secondary);
  font-size: 0.85rem;
  cursor: pointer;
  transition: border-color var(--duration-fast) var(--ease-smooth),
    color var(--duration-fast) var(--ease-smooth),
    background var(--duration-fast) var(--ease-smooth);
}

.tab--active {
  color: var(--text-primary);
  border-color: var(--border-active);
  background: rgba(165, 139, 255, 0.15);
}

.connections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.connection-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-subtle);
  background: rgba(255, 255, 255, 0.03);
}

.connection-card--clickable {
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: transform var(--duration-fast) var(--ease-smooth),
    border-color var(--duration-fast) var(--ease-smooth),
    background var(--duration-fast) var(--ease-smooth);
}

.connection-card--clickable:hover {
  transform: translateY(-1px);
  border-color: rgba(167, 139, 250, 0.35);
  background: rgba(167, 139, 250, 0.08);
}

.connection-card__avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(165, 139, 255, 0.2);
  border: 1px solid rgba(165, 139, 255, 0.4);
  font-weight: 700;
}

.connection-card__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.connection-card__body {
  flex: 1;
}

.connection-card__name {
  font-weight: 600;
  margin: 0 0 4px;
}

.connection-card__age {
  margin-left: 6px;
  font-weight: 400;
  color: var(--text-muted);
}

.connection-card__meta {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin: 0;
}

.profile-preview-modal {
  position: fixed;
  inset: 0;
  z-index: 220;
  background: rgba(9, 5, 15, 0.66);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
}

.profile-preview-shell {
  position: relative;
  width: min(88vw, 380px);
  max-height: min(78svh, 620px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.profile-preview-close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: white;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  z-index: 2;
}

.profile-preview-photo {
  min-height: 210px;
  max-height: 240px;
  background: linear-gradient(160deg, rgba(18, 8, 32, 0.9), rgba(32, 12, 56, 0.85));
}

.profile-preview-photo img,
.profile-preview-photo__fallback {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.profile-preview-photo__fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  font-weight: 800;
  color: white;
  background: linear-gradient(135deg, rgba(165, 139, 255, 0.4), rgba(255, 159, 199, 0.4));
}

.profile-preview-body {
  padding: 14px 14px 10px;
}

.profile-preview-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  padding: 0 14px 14px;
}

.profile-preview-state {
  min-height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 640px) {
  .profile-preview-shell {
    width: min(92vw, 360px);
  }

  .profile-preview-photo {
    min-height: 190px;
    max-height: 220px;
  }
}

.preview-pop-enter-active,
.preview-pop-leave-active {
  transition: opacity 180ms var(--ease-smooth);
}

.preview-pop-enter-from,
.preview-pop-leave-to {
  opacity: 0;
}

.preview-pop-enter-active .profile-preview-shell,
.preview-pop-leave-active .profile-preview-shell {
  transition: transform 180ms var(--ease-spring), opacity 180ms var(--ease-smooth);
}

.preview-pop-enter-from .profile-preview-shell,
.preview-pop-leave-to .profile-preview-shell {
  transform: translateY(10px) scale(0.96);
  opacity: 0;
}
</style>
