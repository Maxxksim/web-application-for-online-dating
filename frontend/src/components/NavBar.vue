<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-white/60 bg-white/80 px-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] pt-2 backdrop-blur-2xl shadow-[0_-12px_30px_rgba(15,23,42,0.12)]"
  >
    <div class="mx-auto flex max-w-6xl items-center justify-around gap-1">
      <RouterLink
        v-for="item in navItems"
        :key="item.name"
        :to="item.to"
        class="group flex min-w-0 flex-1 flex-col items-center gap-1 rounded-2xl px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-[0.1em] text-slate-500 transition duration-200 hover:bg-cyan-50/70 hover:text-cyan-600"
        :class="route.name === item.name ? 'text-cyan-600' : ''"
      >
        <span class="relative flex h-9 w-9 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 transition duration-200 group-hover:bg-cyan-50/80 group-hover:text-cyan-600" :class="route.name === item.name ? 'bg-cyan-50 text-cyan-600 ring-1 ring-cyan-200 shadow-sm' : ''">
          <component :is="item.icon" class="h-5 w-5" />
          <span
            v-if="item.badge && item.badge > 0"
            class="absolute -right-1 -top-1 min-w-4 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none text-white"
          >
            {{ item.badge > 9 ? '9+' : item.badge }}
          </span>
        </span>
        <span class="truncate text-[10px] leading-none sm:text-[11px]">
          {{ item.label }}
        </span>
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications.js'

import IconDiscover from '@/components/icons/IconDiscover.vue'
import IconProfile from '@/components/icons/IconProfile.vue'
import IconSearch from '@/components/icons/IconSearch.vue'
import IconChat from '@/components/icons/IconChat.vue'
import IconConnections from '@/components/icons/IconConnections.vue'
import IconHome from '@/components/icons/IconHome.vue'

const route = useRoute()
const notificationsStore = useNotificationsStore()

const navItems = computed(() => [
  { name: 'home', label: 'Home', to: '/', icon: IconHome },
  { name: 'discover', label: 'Discover', to: '/discover', icon: IconDiscover },
  { name: 'search-settings', label: 'Filters', to: '/search-settings', icon: IconSearch },
  { name: 'chats', label: 'Chats', to: '/chats', icon: IconChat, badge: notificationsStore.messageCount },
  { name: 'connections', label: 'Connect', to: '/connections', icon: IconConnections, badge: notificationsStore.activityCount },
  { name: 'profile', label: 'Profile', to: '/profile', icon: IconProfile },
])
</script>
