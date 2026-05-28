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
  <div class="page min-h-screen bg-transparent text-slate-900 px-4 py-6 sm:px-6 lg:px-8">
    <div class="page-header max-w-6xl mx-auto mb-8">
      <p class="eyebrow">Chats</p>
      <h1 class="page-title">Messages</h1>
    </div>

    <div class="chat-layout max-w-6xl mx-auto" :class="{ 'chat-layout--has-active': activeChat }">
      <aside class="chat-list glass-panel rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="chat-list__header">
          <p>Inbox</p>
        </div>

        <div v-if="hasLoadedChats && !chats.length" class="chat-list__empty">No chats yet.</div>

        <ul v-else class="chat-list__items">
          <li
            v-for="chat in chats"
            :key="chat.id"
            class="chat-list__item"
            :class="{ 'chat-list__item--active': activeChat?.id === chat.id }"
            @click="selectChat(chat)"
          >
            <div class="chat-list__avatar">
              {{ chatLabel(chat).charAt(0) }}
            </div>
            <div class="chat-list__body">
              <p class="chat-list__title">{{ chatLabel(chat) }}</p>
              <p class="chat-list__meta">{{ chatPreview(chat) }}</p>
            </div>
            <span v-if="chat.unread_count" class="chat-list__badge">{{ chat.unread_count }}</span>
          </li>
        </ul>
      </aside>

      <section class="chat-thread glass-panel rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div v-if="!activeChat" class="chat-empty">
          <p>Select a chat to start messaging.</p>
        </div>

        <div v-else class="chat-thread__inner">
          <div class="chat-thread__header">
            <div class="chat-thread__header-left">
              <button class="chat-back-btn" @click="activeChat = null" aria-label="Back to chats">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="19" y1="12" x2="5" y2="12"></line>
                  <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
              </button>
              <div>
                <p class="chat-thread__title">{{ chatLabel(activeChat) }}</p>
                <p v-if="!activeChat.id" class="chat-thread__meta">Send a message to start the conversation</p>
              </div>
            </div>
          </div>

          <div ref="messagesContainer" class="chat-thread__messages">
            <div v-if="hasLoadedMessages && !messages.length" class="chat-thread__status">No messages yet.</div>
            <div v-else-if="messages.length" class="chat-thread__stack">
              <div
                v-for="message in messages"
                :key="message.id"
                class="chat-bubble"
                :class="{ 'chat-bubble--me': Number(messageSenderId(message)) === Number(currentUserId) }"
              >
                <p class="chat-bubble__text">{{ message.text }}</p>
                <span class="chat-bubble__meta">
                  {{ formatTime(message.created_at) }}
                </span>
              </div>
            </div>
          </div>

          <form class="chat-thread__composer" @submit.prevent="sendMessage">
            <input
              v-model="draft"
              type="text"
              class="chat-thread__input"
              placeholder="Type a message..."
            />
            <BaseButton type="submit" size="sm" variant="primary" :loading="isSending">
              Send
            </BaseButton>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.chat-layout {
  display: grid;
  grid-template-columns: minmax(220px, 300px) 1fr;
  gap: 16px;
}

.chat-back-btn {
  display: none;
  background: none;
  border: none;
  color: var(--text-primary);
  padding: 8px;
  margin-right: 8px;
  margin-left: -8px;
  cursor: pointer;
}

.chat-thread__header-left {
  display: flex;
  align-items: center;
}

.chat-list {
  padding: 14px 0 6px;
  display: flex;
  flex-direction: column;
  max-height: min(620px, 70vh);
  overflow: hidden;
}

.chat-list__header {
  padding: 0 16px 10px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.chat-list__empty {
  font-size: 0.85rem;
  color: var(--text-muted);
  padding: 0 16px 14px;
}

.chat-list__items {
  list-style: none;
  margin: 0;
  padding: 0 6px 6px;
  overflow-y: auto;
  flex: 1;
}

.chat-list__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 10px;
  border-radius: var(--radius-md);
  cursor: pointer;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.7);
  transition: background var(--duration-fast) var(--ease-smooth),
              border-color var(--duration-fast) var(--ease-smooth),
              transform var(--duration-fast) var(--ease-smooth),
              box-shadow var(--duration-fast) var(--ease-smooth);
}

.chat-list__item:hover {
  background: rgba(14, 165, 233, 0.08);
  border-color: rgba(14, 165, 233, 0.35);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.chat-list__item--active {
  background: rgba(14, 165, 233, 0.14);
  border-color: rgba(14, 165, 233, 0.6);
  box-shadow: var(--shadow-md);
}

.chat-list__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.chat-list__body {
  flex: 1;
  min-width: 0;
}

.chat-list__title {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--text-primary);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chat-list__meta {
  font-size: 0.72rem;
  color: var(--text-muted);
  margin: 2px 0 0;
}

.chat-list__badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: var(--color-rose);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ── Chat thread ── */
.chat-thread {
  display: flex;
  flex-direction: column;
  max-height: min(620px, 70vh);
  min-height: 400px;
  overflow: hidden;
}

.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.chat-thread__inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.chat-thread__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(10px);
}

.chat-thread__title {
  font-weight: 600;
  font-size: 1rem;
  margin: 0;
}

.chat-thread__meta {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin: 2px 0 0;
}

.chat-thread__messages {
  flex: 1;
  overflow-y: auto;
  padding: 14px 18px;
  min-height: 0;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.9) 0%, rgba(248, 250, 252, 0.4) 100%);
}

.chat-thread__status {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.chat-thread__stack {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.chat-bubble {
  max-width: 72%;
  padding: 10px 14px;
  border-radius: 14px;
  background: var(--color-bg);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
}

.chat-bubble--me {
  align-self: flex-end;
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(14, 165, 233, 0.05));
  border-color: rgba(14, 165, 233, 0.25);
}

.chat-bubble__text {
  margin: 0 0 4px;
  font-size: 0.9rem;
  line-height: 1.45;
  color: var(--text-primary);
}

.chat-bubble__meta {
  font-size: 0.68rem;
  color: var(--text-muted);
}

.chat-thread__composer {
  display: flex;
  gap: 8px;
  padding: 12px 18px;
  border-top: 1px solid var(--border-color);
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(10px);
}

.chat-thread__input {
  flex: 1;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid var(--border-color);
  border-radius: 999px;
  padding: 9px 16px;
  color: var(--text-primary);
  font-size: 0.88rem;
  outline: none;
  transition: border-color var(--duration-fast) var(--ease-smooth);
}

.chat-thread__input:focus {
  border-color: var(--color-accent);
}

.chat-thread__input::placeholder {
  color: var(--text-muted);
}

@media (max-width: 900px) {
  .chat-layout {
    display: flex;
    flex-direction: column;
    height: calc(100svh - 170px);
  }
  .chat-layout--has-active .chat-list {
    display: none;
  }
  .chat-layout:not(.chat-layout--has-active) .chat-thread {
    display: none;
  }
  .chat-back-btn {
    display: flex;
  }
  .chat-list {
    max-height: none;
    height: 100%;
    flex: 1;
  }
  .chat-thread {
    max-height: none;
    height: 100%;
    flex: 1;
  }
}
</style>
