<template>
  <div class="min-h-screen bg-transparent text-slate-900">
    <AppLayout>
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </Transition>
      </RouterView>
    </AppLayout>

    <Teleport to="body">
      <ToastContainer />
      <NotificationPopups />
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useNotificationsStore } from '@/stores/notifications.js'
import { useProfileStore } from '@/stores/profile.js'
import ToastContainer from '@/components/ToastContainer.vue'
import NotificationPopups from '@/components/NotificationPopups.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const profileStore = useProfileStore()
const route = useRoute()

const realtimeUserId = computed(() => (
  profileStore.myProfile?.user_id
  || profileStore.myProfile?.userId
  || notificationsStore.notifiableId
))

const syncAuthenticatedData = async () => {
  if (!authStore.isAuthenticated) return
  await Promise.allSettled([
    notificationsStore.fetchAll(),
    profileStore.fetchMyProfile(),
  ])
}

onMounted(() => {
  window.addEventListener('message', (event) => {
    if (event.data?.token) {
      authStore.loginWithGoogleToken(event.data.token)
    }
  })

  syncAuthenticatedData()
})

watch(
  () => authStore.isAuthenticated,
  (isAuthed) => {
    if (isAuthed) syncAuthenticatedData()
    else notificationsStore.stopRealtime()
  }
)

watch(
  [() => authStore.isAuthenticated, () => authStore.token, realtimeUserId],
  ([isAuthed, token, userId]) => {
    if (!isAuthed) return
    if (userId && token) {
      notificationsStore.startRealtime({ userId, token })
    }
  },
  { immediate: true }
)
</script>
