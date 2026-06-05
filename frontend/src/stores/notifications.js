import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi } from '@/api/notifications.js'
import { initReverb, disconnectReverb } from '@/realtime/reverb.js'

export const useNotificationsStore = defineStore('notifications', () => {

  const notifications = ref([])
  const isLoading = ref(false)

  const realtimeState = ref({
    echo: null,
    userId: null,
    token: null,
    chatChannelSubscribed: false,
  })

  const pendingLikes = ref(0)
  const pendingMatches = ref(0)
  const pendingMessages = ref(0)

  const lastLikeEvent = ref(null)
  const lastMatchEvent = ref(null)
  const lastRealtimeMessage = ref(null)
  const lastReadReceipt = ref(null)

  const activeChatId = ref(null)

  const popups = ref([])
  let popupSeq = 0

  const messageCount = computed(() => {
    const fromServer = notifications.value.filter((n) => {
      if (!n.type?.includes('MessageNotification')) return false
      if (!activeChatId.value) return true
      return Number(n.data?.chat_id) !== Number(activeChatId.value)
    }).length
    return Math.max(fromServer, pendingMessages.value)
  })

  const countByType = (typeName) =>
    notifications.value.filter(n => n.type?.includes(typeName)).length

  const likeCount = computed(() => countByType('LikeNotification') + pendingLikes.value)
  const matchCount = computed(() => countByType('MatchNotification') + pendingMatches.value)
  const activityCount = computed(() => likeCount.value + matchCount.value)
  const unreadCount = computed(() => notifications.value.length)
  const hasUnread = computed(() => unreadCount.value > 0)

  const notifiableId = computed(() => {
    const first = notifications.value[0]
    return first?.notifiable_id || first?.notifiableId || null
  })

  function addPopup({ label, message, type }) {
    const id = ++popupSeq
    popups.value.push({ id, label, message, type, timestamp: Date.now() })
    setTimeout(() => removePopup(id), 5000)
  }

  function removePopup(id) {
    popups.value = popups.value.filter(p => p.id !== id)
  }

  function setActiveChatId(id) {
    activeChatId.value = id ? Number(id) : null
  }

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

  function findUnreadProfileNotification(typeName, profileId) {
    if (!profileId) return null
    return notifications.value.find((n) => (
      n.type?.includes(typeName) &&
      Number(n.data?.profile_id) === Number(profileId)
    )) || null
  }

  async function markProfileNotificationAsRead(typeName, profileId) {
    const notification = findUnreadProfileNotification(typeName, profileId)
    if (!notification) return false
    await markAsRead(notification.id)
    return true
  }

  function handleMessageEvent(payload) {
    lastRealtimeMessage.value = { ...payload, _ts: Date.now() }
    
    const chatId = payload.chat_id ? Number(payload.chat_id) : null
    if (chatId && activeChatId.value === chatId) return

    fetchAll()
  }

  function handleMessageReadEvent(payload) {
    lastReadReceipt.value = { ...payload, _ts: Date.now() }
  }

  function subscribeToChatChannel(echo, userId) {
    if (realtimeState.value.chatChannelSubscribed) return

    echo.private(`chats.${userId}`)
      .listen('.message.sent', handleMessageEvent)
      .listen('.message.read', handleMessageReadEvent)

    realtimeState.value.chatChannelSubscribed = true
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

    realtimeState.value = {
      echo,
      userId: normalizedUserId,
      token,
      chatChannelSubscribed: false,
    }

    echo.private(`notifications.${normalizedUserId}`)
      .subscribed(() => {
        
      })
      .listenToAll((event, data) => {
      })
      .notification(async (notification) => {
        const type = notification.type || ''

        if (type.includes('LikeNotification')) {
          pendingLikes.value++
          addPopup({ label: 'Like', message: 'Someone liked you!', type: 'like' })
          await fetchAll()
          pendingLikes.value = 0
          lastLikeEvent.value = Date.now()
        }

        if (type.includes('MatchNotification')) {
          pendingMatches.value++
          addPopup({ label: 'Match', message: 'You have a new match!', type: 'match' })
          await fetchAll()
          pendingMatches.value = 0
          lastMatchEvent.value = Date.now()
        }

        if (type.includes('MessageNotification')) {
          const chatId = notification.chat_id ? Number(notification.chat_id) : null
          if (chatId && activeChatId.value === chatId) return

          pendingMessages.value++
          addPopup({ label: 'Message', message: 'New message received', type: 'message' })
          await fetchAll()
          pendingMessages.value = 0
        }
      })

    echo.private(`likesRetracted.${normalizedUserId}`)
      .listen('.like.retracted', async () => {
        popups.value = popups.value.filter(p => p.type !== 'like')
        await fetchAll()
        lastLikeEvent.value = Date.now()
      })

    subscribeToChatChannel(echo, normalizedUserId)

    return true
  }



  function stopRealtime() {
    const state = realtimeState.value
    if (state.echo && state.userId) {
      state.echo.leave(`notifications.${state.userId}`)
      state.echo.leave(`likes.${state.userId}`)
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
    lastReadReceipt,
    fetchAll, markAsRead, markAllAsRead,
    findUnreadProfileNotification, markProfileNotificationAsRead,
    startRealtime, stopRealtime,
    lastLikeEvent, lastMatchEvent,
    pendingLikes, pendingMatches,
    pendingMessages,
  }
})