<template>
  <div class="relative min-h-screen overflow-hidden bg-[#f8fafc]">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -left-32 -top-24 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_30%_30%,rgba(20,184,166,0.35),rgba(20,184,166,0))]"></div>
      <div class="absolute -right-28 top-10 h-[360px] w-[360px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(56,189,248,0.35),rgba(56,189,248,0))]"></div>
      <div class="absolute bottom-[-180px] left-[12%] h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_40%_40%,rgba(250,204,21,0.25),rgba(250,204,21,0))]"></div>
      <div class="absolute inset-0 bg-[linear-gradient(rgba(15,23,42,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(15,23,42,0.05)_1px,transparent_1px)] bg-[size:90px_90px] opacity-40"></div>
    </div>

    <main class="relative flex-1 pb-20">
      <div class="relative mx-auto min-h-screen max-w-6xl px-6 py-20">
        <slot />
      </div>
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
