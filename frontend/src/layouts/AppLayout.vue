<template>
  <div class="relative min-h-screen overflow-x-hidden bg-[#f8fafc]">
    <!-- Shared ambient background — single source of truth -->
    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">
      <div class="absolute -left-32 -top-24 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_30%_30%,rgba(20,184,166,0.35),rgba(20,184,166,0))] animate-float-slow"></div>
      <div class="absolute -right-28 top-10 h-[360px] w-[360px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(56,189,248,0.35),rgba(56,189,248,0))] animate-float-medium"></div>
      <div class="absolute bottom-[-180px] left-[12%] h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(250,204,21,0.25),rgba(250,204,21,0))] animate-float-fast"></div>
      <div class="absolute -bottom-[100px] right-[5%] h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle_at_50%_50%,rgba(244,114,182,0.25),rgba(244,114,182,0))] animate-float-slow" style="animation-delay: -3s"></div>
      <div class="absolute inset-0 bg-[linear-gradient(rgba(15,23,42,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(15,23,42,0.05)_1px,transparent_1px)] bg-[size:90px_90px] opacity-40"></div>
    </div>

    <!-- Page content -->
    <main class="relative z-10 min-h-screen" :class="{ 'pb-[80px]': isAuthed }">
      <slot />
    </main>

    <NavBar v-if="isAuthed" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth.js'

const authStore = useAuthStore()
const isAuthed = computed(() => authStore.isAuthenticated)
</script>

