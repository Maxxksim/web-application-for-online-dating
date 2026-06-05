<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { chatsApi } from '@/api/chats.js'
import { messagesApi } from '@/api/messages.js'
import { profilesApi } from '@/api/profiles.js'
import { useToast } from '@/composables/useToast.js'
import { useProfileStore } from '@/stores/profile.js'
import { useNotificationsStore } from '@/stores/notifications.js'

const route = useRoute()
const router = useRouter()
const { toast } = useToast()
const profileStore = useProfileStore()
const notificationsStore = useNotificationsStore()

const chats = ref([])
const isLoading = ref(false)
const hasLoadedChats = ref(false)

const activeChat = ref(null)
const messages = ref([])
const isLoadingMessages = ref(false)
const hasLoadedMessages = ref(true)
const messagesPage = ref(1)
const messagesLastPage = ref(1)
const isLoadingOlderMessages = ref(false)
const hasMoreOlderMessages = ref(true)
const draft = ref('')
const desktopMessagesContainer = ref(null)
const mobileMessagesContainer = ref(null)
const isUserScrollingUp = ref(false)
const isRestoringScrollPosition = ref(false)
const showJumpToLatest = ref(false)
const newMessagesCount = ref(0)

const onMessagesScroll = (e) => {
  if (isRestoringScrollPosition.value) return

  const el = e.target
  if (!el) return
  if (!isNearBottom(el)) {
    isUserScrollingUp.value = true
    showJumpToLatest.value = true
  } else {
    isUserScrollingUp.value = false
    showJumpToLatest.value = false
    newMessagesCount.value = 0
  }

  const isNearTop = el.scrollTop <= 120
  if (isNearTop) {
    loadOlderMessages(el)
  }
}

// Group messages with date separators (Telegram-style)
const groupedMessages = computed(() => {
  if (!messages.value.length) return []

  const groups = []
  let currentDateLabel = null

  messages.value.forEach((message) => {
    const now = new Date()
    const yesterday = new Date(now)
    yesterday.setDate(yesterday.getDate() - 1)
    const weekAgo = new Date(now)
    weekAgo.setDate(weekAgo.getDate() - 7)

    const msgDateObj = new Date(message.created_at)

    let dateLabel = null
    if (msgDateObj.toDateString() === now.toDateString()) {
      dateLabel = 'Today'
    } else if (msgDateObj.toDateString() === yesterday.toDateString()) {
      dateLabel = 'Yesterday'
    } else if (msgDateObj >= weekAgo) {
      dateLabel = msgDateObj.toLocaleDateString([], { weekday: 'long' })
    } else {
      dateLabel = msgDateObj.toLocaleDateString([], { day: 'numeric', month: 'long', year: 'numeric' })
    }

    if (dateLabel !== currentDateLabel) {
      currentDateLabel = dateLabel
      groups.push({ type: 'date-label', label: dateLabel })
    }
    groups.push({ type: 'message', message })
  })

  return groups
})

const currentUserId = computed(() => {
  const p = profileStore.myProfile || {}
  return p.user_id ?? p.userId ?? p.id ?? null
})

const otherParticipant = (chat) => {
  if (!chat) return null
  if (chat.interlocutor) return chat.interlocutor
  const users = chat.users || []
  return users.find((u) => Number(u?.id) !== Number(currentUserId.value)) || users[0] || null
}

const chatLabel = (chat) => {
  const other = otherParticipant(chat)
  const name = other?.profile?.name || other?.name
  if (name) return name
  if (other?.id) return `User #${other.id}`
  return chat?.id ? `Chat #${chat.id}` : 'New chat'
}

const chatAvatarUrl = (chat) => {
  const other = otherParticipant(chat)
  const photo = other?.profile?.photos?.[0]
  if (photo?.url) return photo.url
  if (photo?.path) {
    const base = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/api$/, '')
    return `${base}/storage/${photo.path}`
  }
  return other?.profile?.photo_url || other?.photo_url || null
}

const findChatByUserId = (userId) =>
  chats.value.find((c) => Number(otherParticipant(c)?.id) === Number(userId)) || null

const findChatById = (chatId) =>
  chats.value.find((c) => Number(c.id) === Number(chatId)) || null

const messageSenderId = (message) =>
  message?.sender_id
  ?? message?.user_id
  ?? message?.userId
  ?? message?.sender?.user_id
  ?? message?.sender?.userId
  ?? message?.sender?.id
  ?? message?.sender_id
  ?? null

const messageStatus = (message) => message?.status ?? 'sent'

const chatLastMessage = (chat) => {
  if (
    activeChat.value?.id
    && Number(chat?.id) === Number(activeChat.value.id)
    && messages.value.length
  ) {
    return messages.value[messages.value.length - 1]
  }

  return chat?.last_message ?? chat?.lastMessage ?? null
}

const isOwnLastMessage = (chat) => {
  const lastMessage = chatLastMessage(chat)
  if (!lastMessage || !currentUserId.value) return false
  return Number(messageSenderId(lastMessage)) === Number(currentUserId.value)
}

const isLastMessageRead = (chat) => Boolean(chatLastMessage(chat)?.read_at)

const chatPreview = (chat) => {
  const lastText = chatLastMessage(chat)?.text ?? ''
  if (typeof lastText === 'string' && lastText.trim()) return lastText
  return 'No messages yet'
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
}

const normalizeMessage = (payload) => {
  const senderId = messageSenderId(payload)
  const currentId = currentUserId.value ? Number(currentUserId.value) : null
  const other = activeChat.value ? otherParticipant(activeChat.value) : null
  const isMe = senderId && currentId && Number(senderId) === currentId

  let sender = payload.sender || null

  if (!sender?.profile) {
    const profile = isMe
      ? profileStore.myProfile
      : other?.profile || null

    if (profile || senderId) {
      sender = {
        user_id: senderId,
        profile,
      }
    }
  }

  const profile = sender?.profile || null

  return {
    id: payload.id,
    chat_id: payload.chat_id,
    sender_id: senderId,
    recipient_id: payload.recipient_id ?? null,
    user_id: senderId,
    text: payload.text ?? '',
    read_at: payload.read_at ?? null,
    created_at: payload.created_at,
    status: payload.status ?? (String(payload.id || '').startsWith('temp-') ? 'sending' : 'sent'),
    sender,
    user: profile
      ? { id: sender?.user_id ?? senderId, profile, name: profile.name }
      : null,
  }
}

const createOptimisticMessage = (chatId, senderId, text) => ({
  id: `temp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
  chat_id: chatId,
  sender_id: senderId,
  text,
  created_at: new Date().toISOString(),
  status: 'sending',
})

const SCROLL_THRESHOLD = 80

const isNearBottom = (el) => {
  if (!el) return false
  const { scrollTop, scrollHeight, clientHeight } = el
  return scrollHeight - scrollTop - clientHeight < SCROLL_THRESHOLD
}

const getActiveMessagesContainer = () => {
  const containers = [desktopMessagesContainer.value, mobileMessagesContainer.value].filter(Boolean)
  return containers.find((el) => el && el.getClientRects().length > 0) || containers[0] || null
}

const scrollToBottomInstant = () => {
  if (isUserScrollingUp.value) return
  nextTick(() => {
    const el = getActiveMessagesContainer()
    if (el) {
      el.scrollTop = el.scrollHeight
      showJumpToLatest.value = false
      newMessagesCount.value = 0
    }
  })
}

const scrollToBottomSmooth = () => {
  if (isUserScrollingUp.value) return
  nextTick(() => {
    setTimeout(() => {
      const el = getActiveMessagesContainer()
      if (el) {
        el.scrollTo({
          top: el.scrollHeight,
          behavior: 'smooth'
        })
        showJumpToLatest.value = false
        newMessagesCount.value = 0
      }
    }, 50)
  })
}

const jumpToLatestMessages = () => {
  isUserScrollingUp.value = false
  showJumpToLatest.value = false
  newMessagesCount.value = 0
  nextTick(() => {
    const el = getActiveMessagesContainer()
    if (!el) return
    el.scrollTo({
      top: el.scrollHeight,
      behavior: 'smooth',
    })
  })
}

const scrollToBottomIfNear = () => {
  nextTick(() => {
    const el = getActiveMessagesContainer()
    if (!el) return
    const wasNear = isNearBottom(el)
    if (!wasNear) return
    setTimeout(() => {
      el.scrollTo({
        top: el.scrollHeight,
        behavior: 'smooth'
      })
    }, 50)
  })
}

const loadChats = async () => {
  isLoading.value = true
  try {
    const { data } = await chatsApi.getAll()
    console.log(data)
    const list = data.chats || []
    const activeChatId = Number(activeChat.value?.id ?? null)
    chats.value = list.map((chat) => (
      activeChatId && Number(chat.id) === activeChatId
        ? { ...chat, unread_count: 0 }
        : chat
    ))
  } catch {
    toast.error('Unable to load chats.')
  } finally {
    isLoading.value = false
    hasLoadedChats.value = true
  }
}

const clearChatUnreadCount = (chatId) => {
  if (!chatId) return
  chats.value = chats.value.map((chat) => (
    Number(chat.id) === Number(chatId)
      ? { ...chat, unread_count: 0 }
      : chat
  ))
}

const loadMessages = async (chatId) => {
  if (!chatId) return
  hasLoadedMessages.value = false
  isLoadingMessages.value = true
  messagesPage.value = 1
  messagesLastPage.value = 1
  isLoadingOlderMessages.value = false
  hasMoreOlderMessages.value = true
  showJumpToLatest.value = false
  newMessagesCount.value = 0
  try {
    const { data } = await messagesApi.getAll(chatId)
    const rawMessages = data?.messages ?? null
    const list = Array.isArray(rawMessages?.data)
      ? rawMessages.data
      : Array.isArray(rawMessages)
        ? rawMessages
        : Array.isArray(data?.data)
          ? data.data
          : []
    messages.value = [...list].reverse().map((m) => normalizeMessage(m))
    const currentPage = Number(rawMessages?.meta?.current_page ?? 1)
    const lastPage = Number(rawMessages?.meta?.last_page ?? NaN)
    messagesPage.value = Number.isFinite(currentPage) ? currentPage : 1
    messagesLastPage.value = Number.isFinite(lastPage) ? lastPage : Number.MAX_SAFE_INTEGER
    hasMoreOlderMessages.value = Number.isFinite(lastPage)
      ? messagesPage.value < messagesLastPage.value
      : list.length > 0
    scrollToBottomInstant()
    // Ensure messages are marked as read on load (helps when selectChat path didn't call markAsRead)
    try {
      await messagesApi.markAsRead(chatId)
    } catch (err) {
      // non-fatal
    }
  } catch {
    toast.error('Unable to load messages.')
  } finally {
    isLoadingMessages.value = false
    hasLoadedMessages.value = true
  }
}

const loadOlderMessages = async (sourceEl = null) => {
  const chatId = activeChat.value?.id
  if (!chatId || isLoadingMessages.value || isLoadingOlderMessages.value) return
  if (!hasMoreOlderMessages.value || messagesPage.value >= messagesLastPage.value) return

  const container = sourceEl || getActiveMessagesContainer()
  const prevScrollHeight = container?.scrollHeight ?? 0
  const prevScrollTop = container?.scrollTop ?? 0

  let anchorMessageId = null
  let anchorRelativeTop = 0
  if (container) {
    const messageEls = Array.from(container.querySelectorAll('[data-message-id]'))
    const anchorEl = messageEls.find((el) => el.offsetTop >= container.scrollTop) || messageEls[0] || null
    if (anchorEl) {
      anchorMessageId = anchorEl.getAttribute('data-message-id')
      anchorRelativeTop = anchorEl.offsetTop - container.scrollTop
    }
  }

  isLoadingOlderMessages.value = true
  try {
    const nextPage = messagesPage.value + 1
    const { data } = await messagesApi.getAll(chatId, nextPage)
    const rawMessages = data?.messages ?? null
    const list = Array.isArray(rawMessages?.data)
      ? rawMessages.data
      : Array.isArray(rawMessages)
        ? rawMessages
        : Array.isArray(data?.data)
          ? data.data
          : []

    const olderMessages = [...list].reverse().map((m) => normalizeMessage(m))
    const existingIds = new Set(messages.value.map((m) => m.id))
    const uniqueOlder = olderMessages.filter((m) => !existingIds.has(m.id))

    if (uniqueOlder.length) {
      messages.value = [...uniqueOlder, ...messages.value]
      await nextTick()

      if (container) {
        if (anchorMessageId) {
          const escaped = CSS.escape(String(anchorMessageId))
          const anchorAfter = container.querySelector(`[data-message-id="${escaped}"]`)
          if (anchorAfter) {
            isRestoringScrollPosition.value = true
            const targetTop = anchorAfter.offsetTop - anchorRelativeTop
            container.scrollTop = targetTop
            requestAnimationFrame(() => {
              container.scrollTop = targetTop
              requestAnimationFrame(() => {
                isRestoringScrollPosition.value = false
              })
            })
          } else {
            const newScrollHeight = container.scrollHeight
            container.scrollTop = newScrollHeight - prevScrollHeight + prevScrollTop
          }
        } else {
          const newScrollHeight = container.scrollHeight
          container.scrollTop = newScrollHeight - prevScrollHeight + prevScrollTop
        }
      }
    }

    const currentPage = Number(rawMessages?.meta?.current_page ?? nextPage)
    const lastPage = Number(rawMessages?.meta?.last_page ?? NaN)
    messagesPage.value = Number.isFinite(currentPage) ? currentPage : nextPage
    if (Number.isFinite(lastPage)) {
      messagesLastPage.value = lastPage
      hasMoreOlderMessages.value = messagesPage.value < messagesLastPage.value
    } else {
      hasMoreOlderMessages.value = list.length > 0
    }

    if (list.length === 0) {
      hasMoreOlderMessages.value = false
    }

    // If still near top after prepend, continue loading seamlessly.
    if (container && container.scrollTop <= 120 && hasMoreOlderMessages.value) {
      setTimeout(() => {
        loadOlderMessages(container)
      }, 90)
    }
  } catch {
    toast.error('Unable to load older messages.')
  } finally {
    isLoadingOlderMessages.value = false
  }
}

const markChatNotificationsRead = (chatId) => {
  if (!chatId) return
  const chatNotifs = notificationsStore.notifications.filter(
    (n) => n.type?.includes('MessageNotification') && Number(n.data?.chat_id) === Number(chatId)
  )
  chatNotifs.forEach((n) => notificationsStore.markAsRead(n.id))
}

const routeMatchesCurrentSelection = () => {
  const routeChatId = route.query.chat ? Number(route.query.chat) : null
  if (routeChatId) {
    return Number(activeChat.value?.id) === routeChatId
  }

  const routeUserId = route.query.user ? Number(route.query.user) : null
  if (routeUserId) {
    const currentOther = otherParticipant(activeChat.value)
    const routeProfileId = route.query.profile ? Number(route.query.profile) : null
    return (
      Number(currentOther?.id) === routeUserId
      && Number(currentOther?.profile?.id ?? null) === Number(routeProfileId ?? null)
    )
  }

  return !activeChat.value
}

const selectChat = async (chat, { updateRoute = true } = {}) => {
  activeChat.value = chat
  notificationsStore.setActiveChatId(chat?.id ?? null)
  showJumpToLatest.value = false
  newMessagesCount.value = 0

  if (chat?.id) {
    messages.value = []
    await loadMessages(chat.id)
    try {
      await messagesApi.markAsRead(chat.id)
    } catch (err) {
      console.error('Failed to mark chat messages as read', err)
      toast.error('Unable to mark messages as read.')
    }

    markChatNotificationsRead(chat.id)
    clearChatUnreadCount(chat.id)
    try { await notificationsStore.fetchAll() } catch (_) {}
    if (updateRoute) {
      router.replace({ name: 'chats', query: { chat: chat.id } })
    }
    return
  }

  messages.value = []
  hasLoadedMessages.value = true
  showJumpToLatest.value = false
  newMessagesCount.value = 0
}

const openChatWithUser = async (userId, profileId = null) => {
  const existing = findChatByUserId(userId)
  if (existing) {
    await selectChat(existing)
    return
  }

  let otherUser = { id: userId }
  if (profileId) {
    try {
      const { data } = await profilesApi.getById(profileId)
      if (data?.profile) {
        otherUser = { id: userId, profile: data.profile }
      }
    } catch {
      // keep minimal placeholder
    }
  }

  activeChat.value = { id: null, users: [otherUser] }
  messages.value = []
  hasLoadedMessages.value = true
  showJumpToLatest.value = false
  newMessagesCount.value = 0
  notificationsStore.setActiveChatId(null)

  const query = profileId ? { user: userId, profile: profileId } : { user: userId }
  router.replace({ name: 'chats', query })
}

const sendMessage = async () => {
  const other = otherParticipant(activeChat.value)
  if (!activeChat.value || !other?.id || !draft.value.trim()) return

  const text = draft.value.trim()
  const optimisticMsg = createOptimisticMessage(activeChat.value.id, currentUserId.value, text)
  messages.value.push(normalizeMessage(optimisticMsg))
  draft.value = ''
  scrollToBottomSmooth()

  try {
    const { data } = await messagesApi.send(other.id, text)

    const realMsg = data?.message ?? data
    if (realMsg) {
      const idx = messages.value.findIndex((m) => m.id === optimisticMsg.id)
      if (idx !== -1) {
        messages.value[idx] = normalizeMessage({ ...realMsg, status: 'sent' })
        messages.value = [...messages.value]
      }
    } else {

      const idx = messages.value.findIndex((m) => m.id === optimisticMsg.id)
      if (idx !== -1) {
        messages.value[idx] = { ...messages.value[idx], status: 'sent' }
        messages.value = [...messages.value]
      }
    }

    await loadChats()
    const refreshed = findChatByUserId(other.id)
    if (!refreshed) return

    activeChat.value = refreshed
    notificationsStore.setActiveChatId(refreshed.id)
    const tempMessage = messages.value.find((m) => m.chat_id === null || m.chat_id === undefined)
    if (tempMessage) tempMessage.chat_id = refreshed.id
    router.replace({ name: 'chats', query: { chat: refreshed.id } })
  } catch {
    toast.error('Message failed to send.')
    messages.value = messages.value.filter((m) => m.id !== optimisticMsg.id)
  }
}

const selectFromQuery = async () => {
  if (routeMatchesCurrentSelection()) return

  const chatId = route.query.chat ? Number(route.query.chat) : null
  const userId = route.query.user ? Number(route.query.user) : null
  const profileId = route.query.profile ? Number(route.query.profile) : null

  if (chatId) {
    const found = findChatById(chatId)
    if (found) await selectChat(found, { updateRoute: false })
    return
  }

  if (userId) {
    await openChatWithUser(userId, profileId)
  }
}

let __prevBodyOverflow = null

onMounted(async () => {
  try {
    __prevBodyOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
  } catch (e) {
    
  }

  if (!profileStore.myProfile) await profileStore.fetchMyProfile()
  await loadChats()
  await selectFromQuery()
})

onUnmounted(() => {
  notificationsStore.setActiveChatId(null)
  try {
    if (__prevBodyOverflow !== null) document.body.style.overflow = __prevBodyOverflow
    else document.body.style.overflow = ''
  } catch (e) {
    
  }
})

watch(() => notificationsStore.lastRealtimeMessage, async (payload) => {
  if (!payload) return

  const isForActiveChat =
    activeChat.value?.id
    && Number(payload.chat_id) === Number(activeChat.value.id)

  if (isForActiveChat) {
    const msg = normalizeMessage(payload)
    const pendingIndex = messages.value.findIndex((m) => {
      return (
        messageStatus(m) === 'sending'
        && Number(messageSenderId(m)) === Number(messageSenderId(msg))
        && String(m.text || '') === String(msg.text || '')
      )
    })

    if (pendingIndex !== -1) {
      messages.value[pendingIndex] = { ...msg, status: 'sent' }
      scrollToBottomIfNear()
    } else if (!messages.value.some((m) => m.id === msg.id)) {
      messages.value = [...messages.value, { ...msg, status: 'sent' }]
      const activeContainer = getActiveMessagesContainer()
      const isIncoming = Number(messageSenderId(msg)) !== Number(currentUserId.value)
      const shouldShowJump = Boolean(activeContainer) && !isNearBottom(activeContainer) && isIncoming
      if (shouldShowJump) {
        showJumpToLatest.value = true
        newMessagesCount.value += 1
      } else if (activeContainer && isNearBottom(activeContainer)) {
        newMessagesCount.value = 0
      }
      scrollToBottomIfNear()
    }

    const isIncoming = Number(messageSenderId(msg)) !== Number(currentUserId.value)
    if (isIncoming && activeChat.value?.id) {
      try {
        await messagesApi.markAsRead(activeChat.value.id)
      } catch {
        // non-fatal
      }
    }
  }

  await loadChats()
})

watch(() => notificationsStore.lastReadReceipt, (payload) => {
  if (!payload?.chat_id || !payload?.read_at || !payload?.sender_id) return

  const chatId = Number(payload.chat_id)
  const senderId = Number(payload.sender_id)

  // Update sidebar chat list — mark last_message as read
  chats.value = chats.value.map((chat) => {
    if (Number(chat.id) !== chatId) return chat
    const lastMsg = chat.last_message
    if (!lastMsg || Number(messageSenderId(lastMsg)) !== senderId) return chat
    return { ...chat, last_message: { ...lastMsg, read_at: payload.read_at } }
  })
  chats.value = [...chats.value]

  // If this chat is active, update in-chat message checkmarks
  if (activeChat.value?.id && Number(activeChat.value.id) === chatId) {
    messages.value = messages.value.map((m) => {
      if (
        Number(messageSenderId(m)) === senderId &&
        Number(m.chat_id) === chatId &&
        !m.read_at
      ) {
        return { ...m, read_at: payload.read_at }
      }
      return m
    })
    messages.value = [...messages.value]
  }
})

watch(() => [route.query.chat, route.query.user, route.query.profile], selectFromQuery)

watch(hasLoadedMessages, (val) => {
  if (val && activeChat.value && messages.value.length) {
    scrollToBottomInstant()
  }
})

watch(() => messages.value.length, () => {
  if (activeChat.value && messages.value.length) {
    scrollToBottomIfNear()
  }
})
</script>

<template>
  <div class="page flex h-[100dvh] flex-col overflow-hidden px-4 sm:px-6 lg:px-8">
    <div class="shrink-0 h-6"></div>
    <div
      class="mx-auto flex flex-1 min-h-0 w-full max-w-7xl overflow-hidden rounded-3xl border border-white/60 bg-white/40 shadow-[0_8px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl"
      :class="{ 'max-md:flex-col': true }"
    >
      <!-- Desktop Sidebar (visible on md+) -->
      <aside class="hidden md:flex md:flex-col md:w-80 lg:w-96 md:shrink-0 w-full border-r border-slate-200/50 bg-white/60">
        <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-200/50">
          <h2 class="text-xl font-bold tracking-tight text-slate-900">Messages</h2>
        </div>

        <div v-if="hasLoadedChats && !chats.length" class="flex flex-1 items-center justify-center p-6 text-center text-sm text-slate-500">
          No conversations yet. Start matching!
        </div>

        <ul v-else class="flex-1 overflow-y-auto p-3 space-y-1 thin-scroll">
          <li
            v-for="chat in chats"
            :key="chat.id"
            class="group relative flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all duration-200"
            :class="activeChat?.id === chat.id ? 'bg-cyan-50/80 shadow-sm border border-cyan-100/50' : 'hover:bg-white border border-transparent'"
            @click="selectChat(chat)"
          >
            <div class="relative shrink-0 h-12 w-12">
              <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
                <img v-if="chatAvatarUrl(chat)" :src="chatAvatarUrl(chat)" :alt="chatLabel(chat)" class="h-full w-full object-cover" />
                <span v-else class="text-lg font-bold">{{ chatLabel(chat).charAt(0) }}</span>
              </div>
              <div v-if="chat.unread_count" class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm">
                {{ chat.unread_count }}
              </div>
            </div>
            <div class="flex min-w-0 flex-1 flex-col">
              <div class="flex items-center justify-between">
                <p class="truncate text-sm font-bold text-slate-900" :class="{ 'text-cyan-900': activeChat?.id === chat.id }">
                  {{ chatLabel(chat) }}
                </p>
              </div>
              <p class="truncate text-[13px] text-slate-500" :class="{ 'font-semibold text-slate-700': chat.unread_count }">
                <span v-if="isOwnLastMessage(chat)" class="mr-1 inline-flex align-middle" :class="isLastMessageRead(chat) ? 'text-sky-500' : 'text-slate-400'" aria-hidden="true">
                  <svg v-if="isLastMessageRead(chat)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 13l4 4L15 7M9 13l4 4L23 7" /></svg>
                  <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </span>
                {{ chatPreview(chat) }}
              </p>
            </div>
          </li>
        </ul>
      </aside>

      <!-- Mobile Sidebar (visible on small screens when no chat selected) -->
      <aside v-if="!activeChat" class="flex md:hidden w-full flex-col shrink-0 border-r border-slate-200/50 bg-white/60">
        <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-200/50">
          <h2 class="text-xl font-bold tracking-tight text-slate-900">Messages</h2>
        </div>
        <div v-if="hasLoadedChats && !chats.length" class="flex flex-1 items-center justify-center p-6 text-center text-sm text-slate-500">
          No conversations yet. Start matching!
        </div>
        <ul v-else class="flex-1 overflow-y-auto p-3 space-y-1 thin-scroll">
          <li v-for="chat in chats" :key="chat.id" class="group relative flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all duration-200" :class="activeChat?.id === chat.id ? 'bg-cyan-50/80 shadow-sm border border-cyan-100/50' : 'hover:bg-white border border-transparent'" @click="selectChat(chat)">
            <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
              <img v-if="chatAvatarUrl(chat)" :src="chatAvatarUrl(chat)" :alt="chatLabel(chat)" class="h-full w-full object-cover" />
              <span v-else class="text-lg font-bold">{{ chatLabel(chat).charAt(0) }}</span>
            </div>
            <div class="flex min-w-0 flex-1 flex-col">
              <p class="truncate text-sm font-bold text-slate-900">{{ chatLabel(chat) }}</p>
              <p class="truncate text-[13px] text-slate-500">
                <span v-if="isOwnLastMessage(chat)" class="mr-1 inline-flex align-middle" :class="isLastMessageRead(chat) ? 'text-sky-500' : 'text-slate-400'" aria-hidden="true">
                  <svg v-if="isLastMessageRead(chat)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 13l4 4L15 7M9 13l4 4L23 7" /></svg>
                  <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </span>
                {{ chatPreview(chat) }}
              </p>
            </div>
          </li>
        </ul>
      </aside>

      <!-- Chat Thread (Desktop) -->
      <section class="hidden md:flex flex-1 flex-col bg-slate-50/50 min-h-0 relative">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/50 bg-white/60 px-4 backdrop-blur-md sm:px-6">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
              <img v-if="activeChat && chatAvatarUrl(activeChat)" :src="chatAvatarUrl(activeChat)" :alt="chatLabel(activeChat)" class="h-full w-full object-cover" />
              <span v-else class="text-base font-bold">{{ activeChat ? chatLabel(activeChat).charAt(0) : 'M' }}</span>
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900">{{ activeChat ? chatLabel(activeChat) : 'Messages' }}</h2>
              <p v-if="!activeChat" class="text-xs text-slate-500">Select a conversation</p>
            </div>
          </div>
        </div>

        <div ref="desktopMessagesContainer" class="messages-container flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 thin-scroll [overflow-anchor:none]" @scroll="onMessagesScroll">
          <div
            class="pointer-events-none sticky top-0 z-10 mb-2 flex justify-center transition-all duration-200"
            :class="isLoadingOlderMessages ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
            aria-hidden="true"
          >
            <div class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-[11px] font-medium text-slate-500 shadow-sm backdrop-blur">
              Loading older messages...
            </div>
          </div>
          <div v-if="hasLoadedMessages && !messages.length" class="flex h-full items-center justify-center text-sm text-slate-500">
            <div class="text-center">
              <p class="text-lg font-semibold">No messages yet</p>
              <p class="mt-2 text-sm text-slate-400">Start a conversation or select one from the list.</p>
            </div>
          </div>

          <div v-else-if="messages.length" class="min-h-[content] pb-2">
            <template v-for="item in groupedMessages" :key="item.type === 'date-label' ? `date-${item.label}` : item.message.id">
              <!-- Date separator -->
              <div v-if="item.type === 'date-label'" class="flex justify-center py-2 mb-1.5">
                <span class="rounded-full bg-slate-200/80 px-4 py-1 text-xs font-semibold text-slate-500">{{ item.label }}</span>
              </div>

              <!-- Message -->
              <div v-else :data-message-id="item.message.id" class="flex w-full items-end mb-1.5" :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'justify-end' : 'justify-start'">
                <div
                  class="relative w-fit max-w-[92%] rounded-2xl px-3.5 py-2 shadow-sm sm:max-w-[82%]"
                  :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'rounded-br-sm bg-gradient-to-br from-cyan-500 to-cyan-600 text-white' : 'rounded-bl-sm bg-white border border-slate-100 text-slate-800'"
                >
                  <p class="text-[14px] leading-relaxed whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ item.message.text }}</p>
                  <div class="mt-0.5 flex items-center justify-end gap-1 text-[10px] font-medium opacity-70" :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'text-cyan-100' : 'text-slate-400'">
                    <span>{{ formatTime(item.message.created_at) }}</span>
                    <template v-if="Number(messageSenderId(item.message)) === Number(currentUserId)">
                      <svg v-if="messageStatus(item.message) === 'sending'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                      <svg v-else-if="item.message.read_at" class="h-3.5 w-3.5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 13l4 4L15 7M9 13l4 4L23 7" /></svg>
                      <svg v-else-if="messageStatus(item.message) === 'sent'" class="h-3.5 w-3.5 text-cyan-100/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </template>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <div class="shrink-0 border-t border-slate-200/50 bg-white/80 p-3 sm:p-4 backdrop-blur-md">
          <form class="mx-auto flex max-w-4xl items-center gap-3" @submit.prevent="sendMessage">
            <div class="relative flex-1">
              <input v-model="draft" :readonly="!activeChat" :placeholder="activeChat ? 'Type your message...' : 'Select a conversation first'" type="text" class="w-full rounded-full border border-slate-200 bg-white px-5 py-3 pr-12 text-sm text-slate-900 shadow-sm outline-none transition-all placeholder:text-slate-400 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10" />
            </div>
            <button type="submit" :disabled="!activeChat" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-white shadow-md transition-transform hover:scale-105 hover:bg-cyan-500 disabled:opacity-50">
              <svg class="block h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
            </button>
          </form>
        </div>

        <div v-if="showJumpToLatest" class="absolute bottom-24 right-6 z-20 flex flex-col items-center gap-1">
          <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-cyan-600 shadow-lg transition hover:scale-105 hover:bg-white"
            @click="jumpToLatestMessages"
            aria-label="Jump to latest messages"
          >
            <span v-if="newMessagesCount > 0" class="absolute -top-2 -right-1 min-w-[20px] rounded-full bg-sky-500 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white">{{ newMessagesCount > 99 ? '99+' : newMessagesCount }}</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </button>
        </div>
      </section>

      <!-- Chat Thread (Mobile) -->
      <section v-if="activeChat" class="flex md:hidden flex-1 flex-col bg-slate-50/50 min-h-0 relative">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/50 bg-white/60 px-4 backdrop-blur-md sm:px-6">
          <div class="flex items-center gap-3">
            <button class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" @click="activeChat = null">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
                <img v-if="chatAvatarUrl(activeChat)" :src="chatAvatarUrl(activeChat)" :alt="chatLabel(activeChat)" class="h-full w-full object-cover" />
                <span v-else class="text-base font-bold">{{ chatLabel(activeChat).charAt(0) }}</span>
              </div>
              <div>
                <h2 class="text-base font-bold text-slate-900">{{ chatLabel(activeChat) }}</h2>
              </div>
            </div>
          </div>
        </div>
        <div ref="mobileMessagesContainer" class="messages-container flex-1 overflow-y-auto min-h-0 p-4 thin-scroll [overflow-anchor:none]" @scroll="onMessagesScroll">
          <div
            class="pointer-events-none sticky top-0 z-10 mb-2 flex justify-center transition-all duration-200"
            :class="isLoadingOlderMessages ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
            aria-hidden="true"
          >
            <div class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-[11px] font-medium text-slate-500 shadow-sm backdrop-blur">
              Loading older messages...
            </div>
          </div>
          <div v-if="hasLoadedMessages && !messages.length" class="flex h-full items-center justify-center text-sm text-slate-500">
            <div class="text-center"><p class="text-lg font-semibold">No messages yet</p><p class="mt-2 text-sm text-slate-400">Start a conversation or select one from the list.</p></div>
          </div>
          <div v-else-if="messages.length" class="min-h-[content] pb-2">
            <template v-for="item in groupedMessages" :key="item.type === 'date-label' ? `date-${item.label}` : item.message.id">
              <!-- Date separator -->
              <div v-if="item.type === 'date-label'" class="flex justify-center py-2 mb-1.5">
                <span class="rounded-full bg-slate-200/80 px-4 py-1 text-xs font-semibold text-slate-500">{{ item.label }}</span>
              </div>

              <!-- Message -->
              <div v-else :data-message-id="item.message.id" class="flex w-full items-end mb-1.5" :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'justify-end' : 'justify-start'">
                <div
                  class="relative w-fit max-w-[92%] rounded-2xl px-3.5 py-2 shadow-sm"
                  :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'rounded-br-sm bg-gradient-to-br from-cyan-500 to-cyan-600 text-white' : 'rounded-bl-sm bg-white border border-slate-100 text-slate-800'"
                >
                  <p class="text-[14px] leading-relaxed whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ item.message.text }}</p>
                  <div class="mt-0.5 flex items-center justify-end gap-1 text-[10px] font-medium opacity-70" :class="Number(messageSenderId(item.message)) === Number(currentUserId) ? 'text-cyan-100' : 'text-slate-400'">
                    <span>{{ formatTime(item.message.created_at) }}</span>
                    <template v-if="Number(messageSenderId(item.message)) === Number(currentUserId)">
                      <svg v-if="messageStatus(item.message) === 'sending'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                      <svg v-else-if="item.message.read_at" class="h-3.5 w-3.5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 13l4 4L15 7M9 13l4 4L23 7" /></svg>
                      <svg v-else-if="messageStatus(item.message) === 'sent'" class="h-3.5 w-3.5 text-cyan-100/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </template>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
        <div class="shrink-0 border-t border-slate-200/50 bg-white/80 p-3 sm:p-4 backdrop-blur-md">
          <form class="mx-auto flex max-w-4xl items-center gap-3" @submit.prevent="sendMessage">
            <div class="relative flex-1">
              <input v-model="draft" type="text" placeholder="Type your message..." class="w-full rounded-full border border-slate-200 bg-white px-5 py-3 pr-12 text-sm text-slate-900 shadow-sm outline-none transition-all placeholder:text-slate-400 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10" />
            </div>
            <button type="submit" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-white shadow-md transition-transform hover:scale-105 hover:bg-cyan-500">
              <svg class="block h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
            </button>
          </form>
        </div>

        <div v-if="showJumpToLatest" class="absolute bottom-24 right-4 z-20 flex flex-col items-center gap-1">
          <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-cyan-600 shadow-lg transition hover:scale-105 hover:bg-white"
            @click="jumpToLatestMessages"
            aria-label="Jump to latest messages"
          >
            <span v-if="newMessagesCount > 0" class="absolute -top-2 -right-1 min-w-[20px] rounded-full bg-sky-500 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white">{{ newMessagesCount > 99 ? '99+' : newMessagesCount }}</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </button>
        </div>
      </section>
    </div>
    <div class="shrink-0 h-24"></div>
  </div>
</template>