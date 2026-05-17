<template>
  <div class="app-shell flex flex-col">
    <div class="fixed inset-0 pointer-events-none z-0" aria-hidden="true">
      <div class="app-orb app-orb--one animate-float" />
      <div class="app-orb app-orb--two" />
      <div class="app-orb app-orb--three" />
      <div class="app-grid" />
    </div>

    <main class="flex-1 flex flex-col relative z-[1]">
      <slot />
    </main>

    <NavBar />
  </div>
</template>

<script setup>
import NavBar from '@/components/NavBar.vue'
</script>
