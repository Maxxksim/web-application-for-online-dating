<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useNotificationsStore } from '@/stores/notifications.js'
import { profilesApi } from '@/api/profiles.js'
import { useToast } from '@/composables/useToast.js'
import BaseButton from '@/components/BaseButton.vue'

const notificationsStore = useNotificationsStore()
const { toast } = useToast()

const profilesMap = ref({})

const notifications = computed(() => notificationsStore.notifications)
const isLoading = computed(() => notificationsStore.isLoading)

const getProfileId = (notification) =>
  notification.data?.matched_user_profile_id || notification.data?.liked_by_user_profile_id

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
    .map(getProfileId)
    .filter(Boolean)
    .filter((id) => !profilesMap.value[id])

  await Promise.allSettled(
    ids.map(async (id) => {
      try {
        const { data } = await profilesApi.getById(id)
        profilesMap.value[id] = data.profile
      } catch (err) {
        console.error('Failed to load profile', err)
      }
    })
  )
}

const displayNotifications = computed(() =>
  notifications.value.map((notification) => {
    const profileId = getProfileId(notification)
    const profile = profileId ? profilesMap.value[profileId] : null
    const label = isMatch(notification) ? 'Match' : isMessage(notification) ? 'Message' : 'Like'
    const name = profile?.name || notification.data?.sender_name || 'someone'

    return {
      id: notification.id,
      label,
      message: isMatch(notification)
        ? `You matched with ${name}.`
        : isMessage(notification)
          ? `New message from ${name}.`
          : `New like from ${name}.`,
      time: formatRelativeTime(notification.created_at),
      profile,
      initial: name?.[0] || 'M',
    }
  })
)

const refreshNotifications = async () => {
  const result = await notificationsStore.fetchAll()
  if (!result?.success) {
    toast.error('Unable to load notifications.')
  }
  await hydrateProfiles()
}

onMounted(refreshNotifications)

watch(notifications, hydrateProfiles)

const markAsRead = async (id) => {
  await notificationsStore.markAsRead(id)
}

const markAll = async () => {
  await notificationsStore.markAllAsRead()
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <p class="eyebrow">Activity</p>
      <h1 class="page-title">Notifications</h1>
    </div>

    <div class="glass-panel p-4 md:p-6 max-w-lg">
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm uppercase tracking-[0.3em] text-white/60">Unread</p>
        <BaseButton v-if="displayNotifications.length" size="sm" variant="ghost" @click="markAll">
          Mark all as read
        </BaseButton>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div v-if="isLoading" class="text-sm text-white/60">Loading...</div>
        <ul v-else-if="displayNotifications.length > 0" class="flex flex-col gap-3">
          <li
            v-for="notification in displayNotifications"
            :key="notification.id"
            class="glass-panel glass-panel--tight px-4 py-3 flex items-center gap-3 hover:border-violet-400/60 transition cursor-pointer"
            @click="markAsRead(notification.id)"
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
          <p>No notifications yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>
