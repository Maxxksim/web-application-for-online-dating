/**
 * stores/notifications.js — Notifications state (Pinia)
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi } from '@/api/notifications.js'
import { initReverb, disconnectReverb } from '@/realtime/reverb.js'

export const useNotificationsStore = defineStore('notifications', () => {
  // ── State ──
  const notifications = ref([])
  const isLoading     = ref(false)

  const realtimeState = ref({
    echo: null,
    userId: null,
    token: null,
  })

  // ── Getters ──
  const unreadCount = computed(() => notifications.value.length)

  const hasUnread = computed(() => unreadCount.value > 0)

  const notifiableId = computed(() => {
    const first = notifications.value[0]
    return first?.notifiable_id || first?.notifiableId || null
  })

  // ── Actions ──

  async function fetchAll() {
    isLoading.value = true
    try {
      const { data } = await notificationsApi.getAll()
      notifications.value = data.notifications || []
      return { success: true }
    } catch (err) {
      console.error('Failed to fetch notifications', err)
      return { success: false }
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(id) {
    try {
      await notificationsApi.markAsRead(id)
      notifications.value = notifications.value.filter(n => n.id !== id)
    } catch (err) {
      console.error(err)
    }
  }

  async function markAllAsRead() {
    await Promise.allSettled(notifications.value.map(n => markAsRead(n.id)))
    notifications.value = []
  }

  const refreshFromRealtime = async () => {
    if (isLoading.value) return
    await fetchAll()
  }

  function startRealtime({ userId, token } = {}) {
    if (!userId || !token) return false

    const normalizedUserId = String(userId)
    const state = realtimeState.value

    if (state.echo && state.userId === normalizedUserId && state.token === token) {
      return true
    }

    stopRealtime()

    const echo = initReverb({ token })
    if (!echo) return false

    realtimeState.value = { echo, userId: normalizedUserId, token }

    echo.private(`likes.${normalizedUserId}`)
      .listen('.like.processed', refreshFromRealtime)

    echo.private(`matches.${normalizedUserId}`)
      .listen('.match.created', refreshFromRealtime)

    return true
  }

  function stopRealtime() {
    const state = realtimeState.value
    if (state.echo && state.userId) {
      state.echo.leave(`likes.${state.userId}`)
      state.echo.leave(`matches.${state.userId}`)
    }
    disconnectReverb()
    realtimeState.value = { echo: null, userId: null, token: null }
  }

  return {
    notifications, isLoading,
    unreadCount, hasUnread, notifiableId,
    fetchAll, markAsRead, markAllAsRead,
    startRealtime, stopRealtime,
  }
})
