<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { chatsApi } from '@/api/chats.js'
import { messagesApi } from '@/api/messages.js'
import { profilesApi } from '@/api/profiles.js'
import { useToast } from '@/composables/useToast.js'
import { useProfileStore } from '@/stores/profile.js'
import { useNotificationsStore } from '@/stores/notifications.js'
import BaseButton from '@/components/BaseButton.vue'

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
const draft = ref('')
const isSending = ref(false)
const messagesContainer = ref(null)

const currentUserId = computed(() => profileStore.myProfile?.user_id)

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

const findChatByUserId = (userId) =>
  chats.value.find((c) => Number(otherParticipant(c)?.id) === Number(userId)) || null

const findChatById = (chatId) =>
  chats.value.find((c) => Number(c.id) === Number(chatId)) || null

const messageSenderId = (message) =>
  message?.sender_id ?? message?.user_id ?? message?.sender?.user_id ?? null

const chatPreview = (chat) => {
  if (chat.last_message?.text) return chat.last_message.text
  return 'No messages yet'
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
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
    sender,
    user: profile
      ? { id: sender?.user_id ?? senderId, profile, name: profile.name }
      : null,
  }
}

const scrollToBottom = () => {
  nextTick(() => {
    const el = messagesContainer.value
    if (el) el.scrollTop = el.scrollHeight
  })
}

const loadChats = async () => {
  isLoading.value = true
  try {
    const { data } = await chatsApi.getAll()
    chats.value = data.chats || []
  } catch {
    toast.error('Unable to load chats.')
  } finally {
    isLoading.value = false
    hasLoadedChats.value = true
  }
}

const loadMessages = async (chatId) => {
  if (!chatId) return
  hasLoadedMessages.value = false
  isLoadingMessages.value = true
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
    scrollToBottom()
  } catch {
    toast.error('Unable to load messages.')
  } finally {
    isLoadingMessages.value = false
    hasLoadedMessages.value = true
  }
}

const markChatNotificationsRead = (chatId) => {
  if (!chatId) return
  const chatNotifs = notificationsStore.notifications.filter(
    (n) => n.type?.includes('MessageNotification') && Number(n.data?.chat_id) === Number(chatId)
  )
  chatNotifs.forEach((n) => notificationsStore.markAsRead(n.id))
}

const selectChat = async (chat, { updateRoute = true } = {}) => {
  activeChat.value = chat
  notificationsStore.setActiveChatId(chat?.id ?? null)

  if (chat?.id) {
    messages.value = []
    await loadMessages(chat.id)
    markChatNotificationsRead(chat.id)
    if (updateRoute) {
      router.replace({ name: 'chats', query: { chat: chat.id } })
    }
    return
  }

  messages.value = []
  hasLoadedMessages.value = true
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
  notificationsStore.setActiveChatId(null)

  const query = profileId ? { user: userId, profile: profileId } : { user: userId }
  router.replace({ name: 'chats', query })
}

const sendMessage = async () => {
  const other = otherParticipant(activeChat.value)
  if (!activeChat.value || !other?.id || !draft.value.trim()) return

  const text = draft.value.trim()
  const hadChatId = !!activeChat.value?.id
  const tempId = `temp-${Date.now()}`
  
  // Optimistic UI update
  const optimisticMsg = {
    id: tempId,
    chat_id: activeChat.value.id,
    sender_id: currentUserId.value,
    text: text,
    created_at: new Date().toISOString(),
  }
  messages.value.push(normalizeMessage(optimisticMsg))
  scrollToBottom()
  draft.value = ''
  isSending.value = true

  try {
    await messagesApi.send(other.id, text)

    await loadChats()
    const refreshed = findChatByUserId(other.id)
    if (!refreshed) return

    activeChat.value = refreshed
    notificationsStore.setActiveChatId(refreshed.id)
    router.replace({ name: 'chats', query: { chat: refreshed.id } })

    if (!hadChatId) {
      await loadMessages(refreshed.id)
    } else {
      await loadMessages(refreshed.id)
    }
  } catch {
    toast.error('Message failed to send.')
    messages.value = messages.value.filter(m => m.id !== tempId)
  } finally {
    isSending.value = false
  }
}

const selectFromQuery = async () => {
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

onMounted(async () => {
  if (!profileStore.myProfile) await profileStore.fetchMyProfile()
  await loadChats()
  await selectFromQuery()
})

onUnmounted(() => {
  notificationsStore.setActiveChatId(null)
})

watch(() => notificationsStore.lastRealtimeMessage, async (payload) => {
  if (!payload) return

  if (
    activeChat.value?.id
    && Number(payload.chat_id) === Number(activeChat.value.id)
  ) {
    const msg = normalizeMessage(payload)
    if (!messages.value.some((m) => m.id === msg.id)) {
      messages.value = [...messages.value, msg]
      scrollToBottom()
    }
  }

  await loadChats()
})

watch(() => [route.query.chat, route.query.user, route.query.profile], selectFromQuery)
</script>

<template>
  <div class="page px-4 py-4 sm:px-6 lg:px-8">
    <div 
      class="mx-auto flex h-[calc(100vh-140px)] w-full max-w-6xl overflow-hidden rounded-3xl border border-white/60 bg-white/40 shadow-[0_8px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl"
      :class="{ 'max-md:flex-col': true }"
    >
      <!-- Chat List (Sidebar) -->
      <aside 
        class="flex w-full flex-col border-r border-slate-200/50 bg-white/60 md:w-80 lg:w-96"
        :class="{ 'hidden md:flex': activeChat }"
      >
        <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-200/50">
          <h2 class="text-xl font-bold tracking-tight text-slate-900">Inbox</h2>
        </div>

        <div v-if="hasLoadedChats && !chats.length" class="flex flex-1 items-center justify-center p-6 text-center text-sm text-slate-500">
          No conversations yet. Start matching!
        </div>

        <ul v-else class="flex-1 overflow-y-auto p-3 space-y-1">
          <li
            v-for="chat in chats"
            :key="chat.id"
            class="group relative flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all duration-200"
            :class="activeChat?.id === chat.id ? 'bg-cyan-50/80 shadow-sm border border-cyan-100/50' : 'hover:bg-white border border-transparent'"
            @click="selectChat(chat)"
          >
            <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
              <span class="text-lg font-bold">{{ chatLabel(chat).charAt(0) }}</span>
              <div v-if="chat.unread_count" class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm">
                {{ chat.unread_count }}
              </div>
            </div>
            <div class="flex min-w-0 flex-1 flex-col">
              <div class="flex items-center justify-between">
                <p class="truncate text-sm font-bold text-slate-900" :class="{ 'text-cyan-900': activeChat?.id === chat.id }">
                  {{ chatLabel(chat) }}
                </p>
                <!-- Add time if available in chat data, currently missing so omitting or adding logic -->
              </div>
              <p class="truncate text-[13px] text-slate-500" :class="{ 'font-semibold text-slate-700': chat.unread_count }">
                {{ chatPreview(chat) }}
              </p>
            </div>
          </li>
        </ul>
      </aside>

      <!-- Chat Thread -->
      <section 
        class="flex flex-1 flex-col bg-slate-50/50"
        :class="{ 'hidden md:flex': !activeChat }"
      >
        <div v-if="!activeChat" class="hidden flex-1 flex-col items-center justify-center md:flex">
          <div class="flex h-24 w-24 items-center justify-center rounded-full bg-cyan-100/50 text-cyan-500 mb-6 shadow-sm">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-slate-800">Your Messages</h3>
          <p class="mt-2 text-sm text-slate-500 max-w-xs text-center">Select a conversation from the list to start chatting.</p>
        </div>

        <div v-else class="flex h-full flex-col overflow-hidden">
          <!-- Chat Header -->
          <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200/50 bg-white/60 px-4 backdrop-blur-md sm:px-6">
            <div class="flex items-center gap-3">
              <button class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 md:hidden" @click="activeChat = null">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-cyan-100 to-cyan-50 text-cyan-700 shadow-inner">
                  <span class="text-base font-bold">{{ chatLabel(activeChat).charAt(0) }}</span>
                </div>
                <div>
                  <h2 class="text-base font-bold text-slate-900">{{ chatLabel(activeChat) }}</h2>
                  <p v-if="!activeChat.id" class="text-xs text-slate-500">New conversation</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages Area -->
          <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 sm:p-6 scroll-smooth">
            <div v-if="hasLoadedMessages && !messages.length" class="flex h-full items-center justify-center text-sm text-slate-500">
              No messages yet. Send a message to start!
            </div>
            
            <div v-else-if="messages.length" class="flex flex-col gap-4">
              <div
                v-for="(message, idx) in messages"
                :key="message.id"
                class="flex w-full"
                :class="Number(messageSenderId(message)) === Number(currentUserId) ? 'justify-end' : 'justify-start'"
              >
                <div 
                  class="relative max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm sm:max-w-[65%]"
                  :class="Number(messageSenderId(message)) === Number(currentUserId) 
                    ? 'rounded-br-sm bg-gradient-to-br from-cyan-500 to-cyan-600 text-white' 
                    : 'rounded-bl-sm bg-white border border-slate-100 text-slate-800'"
                >
                  <p class="text-[15px] leading-relaxed">{{ message.text }}</p>
                  <div 
                    class="mt-1 text-right text-[10px] font-medium opacity-70"
                    :class="Number(messageSenderId(message)) === Number(currentUserId) ? 'text-cyan-100' : 'text-slate-400'"
                  >
                    {{ formatTime(message.created_at) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Composer -->
          <div class="shrink-0 border-t border-slate-200/50 bg-white/80 p-3 sm:p-4 backdrop-blur-md">
            <form class="mx-auto flex max-w-4xl items-center gap-3" @submit.prevent="sendMessage">
              <div class="relative flex-1">
                <input
                  v-model="draft"
                  type="text"
                  class="w-full rounded-full border border-slate-200 bg-white px-5 py-3 pr-12 text-sm text-slate-900 shadow-sm outline-none transition-all placeholder:text-slate-400 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10"
                  placeholder="Type your message..."
                />
              </div>
              <button 
                type="submit" 
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cyan-600 text-white shadow-md transition-transform hover:scale-105 hover:bg-cyan-500 disabled:opacity-50 disabled:hover:scale-100"
                :disabled="isSending || !draft.trim()"
              >
                <svg v-if="!isSending" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <svg v-else class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </button>
            </form>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
