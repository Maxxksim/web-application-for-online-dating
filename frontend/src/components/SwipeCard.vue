<template>
  <div
    class="swipe-card relative w-full max-w-[520px] md:max-w-[620px] rounded-[28px] overflow-hidden cursor-grab touch-none origin-bottom will-change-transform flex flex-col justify-end"
    :class="{ 'cursor-grabbing': isDragging }"
    :style="cardStyle"
    v-on="handlers"
  >
    <!-- Photo Layer -->
    <div class="absolute inset-0 pointer-events-none z-0">
      <img
        v-if="coverPhoto"
        :src="coverPhoto"
        :alt="profile.name"
        class="w-full h-full object-cover blur-0 transition-[filter] duration-300 pointer-events-none select-none -webkit-user-drag-none"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-violet-900 via-fuchsia-800 to-rose-700"></div>
    </div>

    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-transparent pointer-events-none z-10"></div>

    <!-- Stamps -->
    <div class="absolute inset-x-4 top-8 flex justify-between pointer-events-none z-20">
      <div
        class="border-4 border-emerald-300 text-emerald-200 font-black text-3xl uppercase tracking-widest px-2 py-1 rounded inline-block opacity-0 transition-opacity transform -rotate-[12deg] bg-emerald-400/10"
        :style="{ opacity: likeOpacity }"
      >
        LIKE
      </div>
      <div
        class="border-4 border-rose-400 text-rose-200 font-black text-3xl uppercase tracking-widest px-2 py-1 rounded inline-block opacity-0 transition-opacity transform rotate-[12deg] bg-rose-400/10"
        :style="{ opacity: dislikeOpacity }"
      >
        NOPE
      </div>
    </div>

    <!-- Profile Info -->
    <div class="relative z-20 text-white p-6 pb-8 select-none pointer-events-none">
      <div class="flex items-end gap-2 mb-1">
        <h2 class="text-3xl font-extrabold m-0 leading-none drop-shadow-md text-shadow">{{ profile.name }}</h2>
        <span v-if="profile.age" class="text-2xl font-normal opacity-90 leading-none drop-shadow">{{ profile.age }}</span>
      </div>

      <div class="flex items-center gap-1.5 text-sm opacity-90 drop-shadow mb-2">
        <svg class="w-4 h-4 text-rose-300 drop-shadow" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
        </svg>
        <span>{{ distanceText }}</span>
      </div>

      <p class="text-sm font-medium leading-snug drop-shadow line-clamp-2 md:line-clamp-3 text-white/90">
        {{ profile.description || profile.bio }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSwipe } from '@/composables/useSwipe.js'

const props = defineProps({
  profile: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['swiped'])

const {
  x,
  y,
  rotation,
  isDragging,
  likeOpacity,
  dislikeOpacity,
  handlers,
  triggerSwipe,
} = useSwipe((isLiked) => emit('swiped', isLiked ? 'like' : 'dislike'))

const cardStyle = computed(() => ({
  transform: `translate3d(${x.value}px, ${y.value}px, 0) rotate(${rotation.value}deg)`,
  width: 'min(96vw, 620px)',
  height: 'clamp(420px, calc(100svh - 270px), 660px)',
}))

const coverPhoto = computed(() => {
  return props.profile.photos?.[0]?.url || props.profile.photo_url || null
})

const distanceText = computed(() => {
  const km = props.profile.distance
  if (km === undefined || km === null) return 'Distance unknown'
  if (km < 1) return 'Nearby'
  return `${Math.round(km)} km`
})

defineExpose({ triggerSwipe })
</script>
<style scoped>
.swipe-card {
  background: linear-gradient(160deg, rgba(18, 8, 32, 0.9), rgba(32, 12, 56, 0.85));
  border: 1px solid rgba(167, 139, 250, 0.2);
  box-shadow: 0 24px 60px rgba(10, 6, 20, 0.6);
  transition: transform 0.25s var(--ease-spring), box-shadow 0.25s var(--ease-smooth);
}

.swipe-card:hover {
  box-shadow: 0 30px 70px rgba(10, 6, 20, 0.65);
}

.text-shadow {
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}
</style>
