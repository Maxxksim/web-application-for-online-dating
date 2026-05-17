<template>
  <div id="app" class="h-full min-h-screen">
    <AppLayout v-if="!route.meta.public">
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" :key="route.name" />
        </Transition>
      </RouterView>
    </AppLayout>
    <RouterView v-else />

    <Teleport to="body">
      <ToastContainer />
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
