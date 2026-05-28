/**
 * stores/notifications.js — Notifications state (Pinia)
 *
 * Categorised counts for NavBar badges, popup queue for
 * top-right toast-style notifications, and realtime subscriptions
 * for likes, matches, and incoming chat messages.
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
    chatChannelSubscribed: false,
  })

  // Currently active chat (set by ChatsView) — suppress popups for it
  const activeChatId = ref(null)

  // Popup notification queue (top-right corner)
  const popups = ref([])
  let popupSeq = 0

  // Reactive ref: last message received via realtime (watched by ChatsView)
  const lastRealtimeMessage = ref(null)

  // ── Getters ──
  const likeCount = computed(() =>
    notifications.value.filter(n => n.type?.includes('LikeNotification')).length
  )
  const matchCount = computed(() =>
    notifications.value.filter(n => n.type?.includes('MatchNotification')).length
  )
  const messageCount = computed(() =>
    notifications.value.filter(n => n.type?.includes('MessageNotification')).length
  )
  const activityCount = computed(() => likeCount.value + matchCount.value)
  const unreadCount   = computed(() => notifications.value.length)
  const hasUnread     = computed(() => unreadCount.value > 0)

  const notifiableId = computed(() => {
    const first = notifications.value[0]
    return first?.notifiable_id || first?.notifiableId || null
  })

  // ── Popups ──
  function addPopup({ label, message, type }) {
    const id = ++popupSeq
    popups.value.push({ id, label, message, type, timestamp: Date.now() })
    setTimeout(() => removePopup(id), 5000)
  }
  function removePopup(id) {
    popups.value = popups.value.filter(p => p.id !== id)
  }

  // ── Active chat ──
  function setActiveChatId(id) {
    activeChatId.value = id ? Number(id) : null
  }

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

  // ── Realtime event handlers ──
  function handleLikeEvent() {
    addPopup({ label: 'Like', message: 'Someone liked you!', type: 'like' })
    fetchAll()
  }

  function handleLikeRetractedEvent() {
    // Optionally remove notification if it was retracted, fetchAll will refresh the list.
    fetchAll()
  }

  function handleMatchEvent() {
    addPopup({ label: 'Match', message: 'You have a new match!', type: 'match' })
    fetchAll()
  }

  function handleMessageEvent(payload) {
    const chatId = payload?.chat_id ? Number(payload.chat_id) : null
    const senderId = payload?.sender_id ? Number(payload.sender_id) : null
    const myId = realtimeState.value.userId ? Number(realtimeState.value.userId) : null

    lastRealtimeMessage.value = { ...payload, _ts: Date.now() }

    if (chatId && activeChatId.value === chatId) return
    if (senderId && myId && senderId === myId) return

    addPopup({ label: 'Message', message: 'New message received', type: 'message' })
    fetchAll()
  }

  /** Private channel chats.{userId} — only the authenticated user may listen. */
  function subscribeToChatChannel(echo, userId) {
    const channelName = `chats.${userId}`
    if (realtimeState.value.chatChannelSubscribed) return

    echo.private(channelName)
      .listen('.message.sent', handleMessageEvent)

    realtimeState.value.chatChannelSubscribed = true
  }

  // ── Realtime lifecycle ──
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

    realtimeState.value = {
      echo,
      userId: normalizedUserId,
      token,
      chatChannelSubscribed: false,
    }

    echo.private(`likes.${normalizedUserId}`)
      .listen('.like.processed', handleLikeEvent)
      .listen('.like.retracted', handleLikeRetractedEvent)

    echo.private(`matches.${normalizedUserId}`)
      .listen('.match.created', handleMatchEvent)

    subscribeToChatChannel(echo, normalizedUserId)

    return true
  }

  function stopRealtime() {
    const state = realtimeState.value
    if (state.echo && state.userId) {
      state.echo.leave(`likes.${state.userId}`)
      state.echo.leave(`matches.${state.userId}`)
      state.echo.leave(`chats.${state.userId}`)
    }
    disconnectReverb()
    realtimeState.value = {
      echo: null,
      userId: null,
      token: null,
      chatChannelSubscribed: false,
    }
  }

  return {
    notifications, isLoading,
    likeCount, matchCount, messageCount, activityCount,
    unreadCount, hasUnread, notifiableId,
    popups, addPopup, removePopup,
    activeChatId, setActiveChatId,
    lastRealtimeMessage,
    fetchAll, markAsRead, markAllAsRead,
    startRealtime, stopRealtime,
  }
})
