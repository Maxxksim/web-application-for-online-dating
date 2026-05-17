<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { chatsApi } from '@/api/chats.js'
import { messagesApi } from '@/api/messages.js'
import { useToast } from '@/composables/useToast.js'
import { useProfileStore } from '@/stores/profile.js'
import { initReverb } from '@/realtime/reverb.js'
import BaseButton from '@/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const { toast } = useToast()
const profileStore = useProfileStore()

const chats = ref([])
const isLoading = ref(false)

const activeChat = ref(null)
const messages = ref([])
const isLoadingMessages = ref(false)
const draft = ref('')
const isSending = ref(false)
const chatEcho = ref(null)
const subscribedChatId = ref(null)

const currentUserId = computed(() => profileStore.myProfile?.user_id)

const otherUserId = (chat) => chat?.users?.[0]?.id || null
const otherParticipant = (chat) => chat?.interlocutor || chat?.users?.find((user) => user?.id !== currentUserId.value) || null
const chatLabel = (chat) => {
  const otherId = otherUserId(chat)
  return otherId ? `User #${otherId}` : `Chat #${chat.id}`
}

const normalizeMessage = (payload) => {
  const senderId = payload?.user_id ?? null
  const currentId = currentUserId.value ? Number(currentUserId.value) : null
  const other = activeChat.value ? otherParticipant(activeChat.value) : null
  const senderProfile = senderId && currentId && Number(senderId) === currentId
    ? profileStore.myProfile
    : other?.profile || other?.interlocutor?.profile || null

  return {
    id: payload.id,
    chat_id: payload.chat_id,
    user_id: senderId,
    text: payload.message ?? payload.text ?? '',
    created_at: payload.created_at,
    user: senderProfile ? { ...senderProfile, profile: senderProfile } : payload.user || null,
  }
}

const stopChatRealtime = () => {
  if (chatEcho.value && subscribedChatId.value) {
    chatEcho.value.leave(`chat.${subscribedChatId.value}`)
  }
  chatEcho.value = null
  subscribedChatId.value = null
}

const startChatRealtime = (chatId) => {
  const normalizedChatId = Number(chatId)
  if (!Number.isInteger(normalizedChatId) || normalizedChatId <= 0) return
  if (subscribedChatId.value === normalizedChatId) return

  stopChatRealtime()

  const echo = initReverb()
  if (!echo) return

  chatEcho.value = echo
  subscribedChatId.value = normalizedChatId

  echo.private(`chat.${normalizedChatId}`).listen('.message.sent', (payload) => {
    const message = normalizeMessage(payload)
    if (!message.chat_id || Number(message.chat_id) !== Number(activeChat.value?.id)) return
    if (messages.value.some((item) => item.id === message.id)) return

    messages.value = [...messages.value, message]
  })
}

const loadChats = async () => {
  isLoading.value = true
  try {
    const { data } = await chatsApi.getAll()
    chats.value = data.chats || []
  } catch (err) {
    toast.error('Unable to load chats.')
  } finally {
    isLoading.value = false
  }
}

const loadMessages = async (chatId) => {
  if (!chatId) return
  isLoadingMessages.value = true
  try {
    const { data } = await messagesApi.getAll(chatId)
    const list = data.messages?.data || []
    messages.value = [...list].reverse()
  } catch (err) {
    toast.error('Unable to load messages.')
  } finally {
    isLoadingMessages.value = false
  }
}

const selectChat = async (chat) => {
  activeChat.value = chat
  await loadMessages(chat.id)
  startChatRealtime(chat.id)
}

const sendMessage = async () => {
  if (!activeChat.value || !draft.value.trim()) return
  isSending.value = true
  try {
    await messagesApi.send(activeChat.value.id, draft.value.trim())
    draft.value = ''
  } catch (err) {
    toast.error('Message failed to send.')
  } finally {
    isSending.value = false
  }
}

const selectFromQuery = async () => {
  const chatId = route.query.chat ? Number(route.query.chat) : null
  if (!chatId || !chats.value.length) return
  const found = chats.value.find((chat) => chat.id === chatId)
  if (found) await selectChat(found)
}

onMounted(async () => {
  if (!profileStore.myProfile) await profileStore.fetchMyProfile()
  await loadChats()
  await selectFromQuery()
})

onUnmounted(() => {
  stopChatRealtime()
})

watch(() => route.query.chat, selectFromQuery)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <p class="eyebrow">Chats</p>
      <h1 class="page-title">Messages</h1>
    </div>

    <div class="chat-layout">
      <aside class="chat-list glass-panel">
        <div class="chat-list__header">
          <p class="text-sm uppercase tracking-[0.3em] text-white/60">Inbox</p>
        </div>

        <div v-if="isLoading" class="text-sm text-white/60 px-4 pb-4">Loading chats...</div>
        <div v-else-if="!chats.length" class="text-sm text-white/60 px-4 pb-4">No chats yet.</div>

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
              <p class="chat-list__meta">Chat #{{ chat.id }}</p>
            </div>
            <span v-if="chat.unread_count" class="chat-list__badge">{{ chat.unread_count }}</span>
          </li>
        </ul>
      </aside>

      <section class="chat-thread glass-panel">
        <div v-if="!activeChat" class="chat-empty">
          <p class="text-sm text-white/70">Select a chat to start messaging.</p>
        </div>

        <div v-else class="chat-thread__inner">
          <div class="chat-thread__header">
            <div>
              <p class="chat-thread__title">{{ chatLabel(activeChat) }}</p>
              <p class="chat-thread__meta">Chat #{{ activeChat.id }}</p>
            </div>
            <span v-if="activeChat.unread_count" class="chip">{{ activeChat.unread_count }} unread</span>
          </div>

          <div class="chat-thread__messages">
            <div v-if="isLoadingMessages" class="text-sm text-white/60">Loading messages...</div>
            <div v-else-if="!messages.length" class="text-sm text-white/60">No messages yet.</div>
            <div v-else class="chat-thread__stack">
              <div
                v-for="message in messages"
                :key="message.id"
                class="chat-bubble"
                :class="{ 'chat-bubble--me': message.user_id === currentUserId }"
              >
                <p class="chat-bubble__text">{{ message.text }}</p>
                <span class="chat-bubble__meta">
                  {{ message.user?.profile?.name || message.user?.name || 'User' }}
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
  grid-template-columns: minmax(240px, 320px) 1fr;
  gap: 20px;
}

.chat-list {
  padding: 16px 0 8px;
  height: min(640px, 70vh);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-list__header {
  padding: 0 20px 12px;
}

.chat-list__items {
  list-style: none;
  margin: 0;
  padding: 0 8px 8px;
  overflow-y: auto;
}

.chat-list__item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 12px;
  border-radius: var(--radius-md);
  cursor: pointer;
  border: 1px solid transparent;
  transition: border-color var(--duration-fast) var(--ease-smooth),
              background var(--duration-fast) var(--ease-smooth);
}

.chat-list__item:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: var(--border-subtle);
}

.chat-list__item--active {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--border-active);
}

.chat-list__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(165, 139, 255, 0.2);
  border: 1px solid rgba(165, 139, 255, 0.4);
  font-weight: 700;
}

.chat-list__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.chat-list__title {
  font-weight: 600;
  font-size: 0.95rem;
}

.chat-list__meta {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.chat-list__badge {
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 999px;
  background: rgba(251, 113, 133, 0.2);
  color: #ffd1d8;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-thread {
  padding: 20px;
  min-height: 400px;
  display: flex;
  flex-direction: column;
}

.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-thread__inner {
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 100%;
}

.chat-thread__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-subtle);
}

.chat-thread__title {
  font-weight: 700;
  font-size: 1.05rem;
}

.chat-thread__meta {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.chat-thread__messages {
  flex: 1;
  overflow-y: auto;
  padding-right: 6px;
}

.chat-thread__stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.chat-bubble {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--border-subtle);
}

.chat-bubble--me {
  align-self: flex-end;
  background: rgba(165, 139, 255, 0.18);
  border-color: rgba(165, 139, 255, 0.45);
}

.chat-bubble__text {
  margin: 0 0 6px;
  font-size: 0.92rem;
}

.chat-bubble__meta {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.chat-thread__composer {
  display: flex;
  gap: 10px;
  padding-top: 12px;
  border-top: 1px solid var(--border-subtle);
}

.chat-thread__input {
  flex: 1;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-subtle);
  border-radius: 999px;
  padding: 10px 16px;
  color: var(--text-primary);
  outline: none;
}

.chat-thread__input:focus {
  border-color: var(--border-active);
}

@media (max-width: 900px) {
  .chat-layout {
    grid-template-columns: 1fr;
  }
  .chat-list {
    height: auto;
  }
}
</style>
