<template>
  <nav class="fixed inset-x-0 bottom-0 z-50 pb-[env(safe-area-inset-bottom)] bg-transparent">
    <div class="flex w-full items-center justify-between px-4 py-2">
      <RouterLink
        v-for="item in navItems"
        :key="item.name"
        :to="item.to"
        class="group relative flex flex-1 flex-col items-center justify-center rounded-2xl py-2 mx-1 transition-all duration-300 bg-white/40 backdrop-blur-md border border-white/50 shadow-[0_4px_16px_rgba(0,0,0,0.03)]"
        :class="route.name === item.name ? 'text-cyan-600 bg-white/60' : 'text-slate-500 hover:text-cyan-500 hover:bg-white/60'"
      >
        <span class="relative z-10 flex flex-col items-center gap-1.5">
          <div 
            class="relative flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-300"
            :class="route.name === item.name ? 'bg-cyan-100/60 shadow-[0_2px_8px_rgba(6,182,212,0.18)]' : 'group-hover:bg-cyan-50'"
          >
            <component 
              :is="item.icon" 
              class="h-5 w-5 transition-transform duration-300" 
              :class="route.name === item.name ? 'scale-110 drop-shadow-sm' : ''"
            />
            <span
              v-if="item.badge && item.badge > 0"
              class="absolute -right-1 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white shadow-sm ring-2 ring-white/80"
            >
              {{ item.badge > 9 ? '9+' : item.badge }}
            </span>
            <span
              v-if="item.premium"
              class="absolute -right-1 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 px-1 text-[10px] font-bold leading-none text-white shadow-sm ring-2 ring-white/80"
            >
              ★
            </span>
          </div>
          <span 
            class="text-[9px] font-bold uppercase tracking-wider transition-opacity" 
            :class="route.name === item.name ? 'opacity-100' : 'opacity-80'"
          >
            {{ item.label }}
          </span>
        </span>
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications.js'
import { useSubscriptionStore } from '@/stores/subscription.js'

import IconDiscover from '@/components/icons/IconDiscover.vue'
import IconProfile from '@/components/icons/IconProfile.vue'
import IconSearch from '@/components/icons/IconSearch.vue'
import IconChat from '@/components/icons/IconChat.vue'
import IconConnections from '@/components/icons/IconConnections.vue'

const route = useRoute()
const notificationsStore = useNotificationsStore()
const subscriptionStore = useSubscriptionStore()

const navItems = computed(() => [
  { name: 'discover',       label: 'Discover',to: '/discover',        icon: IconDiscover    },
  { name: 'search-settings',label: 'Filters', to: '/search-settings', icon: IconSearch,      premium: subscriptionStore.isPremium },
  { name: 'chats',          label: 'Chats',   to: '/chats',           icon: IconChat,       badge: notificationsStore.messageCount },
  { name: 'connections',    label: 'Connect', to: '/connections',     icon: IconConnections,badge: notificationsStore.activityCount },
  { name: 'profile',        label: 'Profile', to: '/profile',         icon: IconProfile     },
])
</script>

