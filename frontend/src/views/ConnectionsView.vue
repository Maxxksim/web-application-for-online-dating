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
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const { toast } = useToast()
const profileStore = useProfileStore()

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
    previewProfile.value = { ...(data?.profile || {}), ...like, profile_id: profileId }
  } catch {
    previewProfile.value = { ...like, profile_id: profileId }
  } finally {
    previewLoading.value = false
  }
}

const closePreview = () => {
  if (previewActionLoading.value) return
  previewProfile.value = null
}

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
  <div class="page min-h-screen bg-transparent text-slate-900 px-4 py-6 sm:px-6 lg:px-8">
    <div class="page-header max-w-6xl mx-auto mb-8">
      <p class="eyebrow">Connections</p>
      <h1 class="page-title">Connections</h1>
    </div>

    <div class="tabs flex flex-wrap gap-3 mb-6 max-w-6xl mx-auto">
      <button class="tab" :class="{ 'tab--active': activeTab === 'likes' }" @click="activeTab = 'likes'">Likes</button>
      <button class="tab" :class="{ 'tab--active': activeTab === 'matches' }" @click="activeTab = 'matches'">Matches</button>
    </div>

    <!-- Likes -->
    <section v-if="activeTab === 'likes'" class="glass-panel section-panel rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div v-if="hasLoadedLikes && !likes.length" class="section-status">No likes yet.</div>
      <div v-else class="connections-grid">
        <button
          v-for="(like, i) in likes"
          :key="like.profile_id || like.id || like.user_id || like.userId || i"
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

    <!-- Matches -->
    <section v-else-if="activeTab === 'matches'" class="glass-panel section-panel rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div v-if="hasLoadedMatches && !matches.length" class="section-status">No matches yet.</div>
      <div v-else class="connections-grid">
        <div v-for="(match, i) in matches" :key="match.id || match.user_id || match.userId || i" class="connection-card rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
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
          <BaseButton size="sm" variant="secondary" :disabled="!getMatchUserId(match)" @click="startChat(match)">
            Chat
          </BaseButton>
        </div>
      </div>
    </section>

    <!-- Preview modal -->
    <transition name="preview-pop">
      <div v-if="previewProfile" class="preview-modal" @click.self="closePreview">
        <div class="preview-shell glass-panel">
          <button class="preview-close" type="button" @click="closePreview">×</button>

          <div v-if="previewLoading" class="preview-loading">Loading profile...</div>

          <template v-else>
            <div class="preview-photo">
              <img v-if="previewProfile.photos?.[0]?.url" :src="previewProfile.photos[0].url" :alt="previewProfile.name || 'Profile'" />
              <div v-else class="preview-photo__fallback">
                {{ previewProfile.name?.[0] || 'M' }}
              </div>
            </div>

            <div class="preview-body">
              <div class="preview-body__name-row">
                <h2 class="preview-body__name">{{ previewProfile.name || 'Someone' }}</h2>
                <span v-if="previewProfile.age" class="preview-body__age">{{ previewProfile.age }}</span>
              </div>
              <p class="preview-body__location">{{ previewProfile.city || 'Nearby' }}</p>
              <p class="preview-body__bio">
                {{ previewProfile.description || previewProfile.bio || 'No bio provided.' }}
              </p>
            </div>

            <div class="preview-actions">
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
  gap: 10px;
  flex-wrap: wrap;
}

.tab {
  padding: 8px 18px;
  border-radius: 999px;
  border: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.85);
  color: var(--text-secondary);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: border-color var(--duration-fast), color var(--duration-fast), background var(--duration-fast), box-shadow var(--duration-fast);
}

.tab--active {
  color: var(--color-accent);
  border-color: rgba(14, 165, 233, 0.45);
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(14, 165, 233, 0.05));
  box-shadow: var(--shadow-md);
}

.section-panel {
  padding: 18px;
}

.section-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.section-panel__label {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin: 0;
}

.section-status {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.section-empty {
  text-align: center;
  color: var(--text-muted);
  padding: 40px 0;
}

/* Connections grid */
.connections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
}

.connection-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.9);
  box-shadow: var(--shadow-sm);
}

.connection-card--clickable {
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: transform var(--duration-fast), border-color var(--duration-fast), background var(--duration-fast), box-shadow var(--duration-fast);
}

.connection-card--clickable:hover {
  transform: translateY(-2px);
  border-color: rgba(14, 165, 233, 0.45);
  background: rgba(14, 165, 233, 0.08);
  box-shadow: var(--shadow-md);
}

.connection-card__avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(14, 165, 233, 0.05));
  color: var(--color-accent);
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: inset 0 0 0 1px rgba(14, 165, 233, 0.2);
}

.connection-card__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.connection-card__body {
  flex: 1;
  min-width: 0;
}

.connection-card__name {
  font-weight: 600;
  margin: 0 0 2px;
  font-size: 0.9rem;
}

.connection-card__age {
  margin-left: 4px;
  font-weight: 400;
  color: var(--text-muted);
}

.connection-card__meta {
  font-size: 0.78rem;
  color: var(--text-muted);
  margin: 0;
}

/* Preview modal */
.preview-modal {
  position: fixed;
  inset: 0;
  z-index: 220;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.preview-shell {
  position: relative;
  width: min(88vw, 380px);
  max-height: min(78svh, 580px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.preview-close {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
  z-index: 2;
}

.preview-photo {
  min-height: 200px;
  max-height: 230px;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(14, 165, 233, 0.05));
}

.preview-photo img,
.preview-photo__fallback {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.preview-photo__fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--color-accent);
  background: var(--color-accent-muted);
}

.preview-body {
  padding: 14px 16px 8px;
}

.preview-body__name-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 2px;
}

.preview-body__name {
  font-size: 1.3rem;
  font-weight: 800;
  margin: 0;
  color: var(--text-primary);
}

.preview-body__age {
  font-size: 1rem;
  color: var(--text-secondary);
}

.preview-body__location {
  font-size: 0.78rem;
  color: var(--text-muted);
  margin: 0 0 8px;
}

.preview-body__bio {
  font-size: 0.88rem;
  color: var(--text-secondary);
  line-height: 1.4;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.preview-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  padding: 12px 16px 16px;
}

.preview-loading {
  min-height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
}

.preview-pop-enter-active,
.preview-pop-leave-active {
  transition: opacity 180ms var(--ease-smooth);
}

.preview-pop-enter-from,
.preview-pop-leave-to {
  opacity: 0;
}

.preview-pop-enter-active .preview-shell,
.preview-pop-leave-active .preview-shell {
  transition: transform 180ms var(--ease-spring), opacity 180ms var(--ease-smooth);
}

.preview-pop-enter-from .preview-shell,
.preview-pop-leave-to .preview-shell {
  transform: translateY(8px) scale(0.97);
  opacity: 0;
}

@media (max-width: 640px) {
  .preview-shell {
    width: min(92vw, 360px);
  }
}
</style>
