<template>
  <nav class="nav-glass fixed bottom-0 left-0 right-0 flex justify-around items-center px-4 py-3 z-[100] rounded-t-3xl pb-safe">
    <RouterLink
      v-for="item in navItems"
      :key="item.name"
      :to="item.to"
      class="flex flex-col items-center gap-1 p-1 no-underline text-[0.6rem] uppercase tracking-[0.2em] text-violet-200/70 transition-all relative"
      :class="{ 'text-white': route.name === item.name }"
    >
      <div class="relative w-8 h-8 flex flex-col items-center justify-center">
        <div
          class="absolute inset-0 rounded-full opacity-0 transition-opacity"
          :class="{ 'opacity-100': route.name === item.name }"
          :style="{ background: 'radial-gradient(circle, rgba(139,92,246,0.35) 0%, transparent 70%)' }"
        />
        <component
          :is="item.icon"
          class="w-5 h-5 transition-transform"
          :class="{ 'scale-110 text-white drop-shadow': route.name === item.name }"
        />
        <span v-if="item.badge && item.badge > 0" class="absolute -top-1 -right-2 min-w-[17px] h-[17px] px-1 bg-rose-500 text-white text-[0.6rem] font-bold rounded-full flex items-center justify-center">
          {{ item.badge > 9 ? '9+' : item.badge }}
        </span>
      </div>
      <span class="font-semibold tracking-[0.22em]">{{ item.label }}</span>
    </RouterLink>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications.js'

import IconDiscover       from '@/components/icons/IconDiscover.vue'
import IconProfile        from '@/components/icons/IconProfile.vue'
import IconSearch         from '@/components/icons/IconSearch.vue'
import IconChat           from '@/components/icons/IconChat.vue'
import IconConnections    from '@/components/icons/IconConnections.vue'

const route = useRoute()
const notificationsStore = useNotificationsStore()

const navItems = computed(() => [
  { name: 'discover',         label: 'Discover',   to: '/discover',         icon: IconDiscover },
  { name: 'search-settings',  label: 'Filters',    to: '/search-settings',  icon: IconSearch },
  { name: 'chats',            label: 'Chats',      to: '/chats',            icon: IconChat },
  { name: 'connections',      label: 'Connect',    to: '/connections',      icon: IconConnections, badge: notificationsStore.unreadCount },
  { name: 'profile',          label: 'Profile',    to: '/profile',          icon: IconProfile },
])
</script>
