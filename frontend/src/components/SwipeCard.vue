<template>
  <div
    class="relative overflow-hidden cursor-grab touch-none origin-bottom flex flex-col justify-end rounded-[20px] border border-slate-200/50 shadow-lg bg-white/50 transition-[transform,box-shadow] duration-[350ms] ease-out will-change-transform"
    :class="{ 'cursor-grabbing': isDragging }"
    :style="cardStyle"
    v-on="handlers"
  >
    <!-- Photo Layer -->
    <div class="absolute inset-0 pointer-events-none">
      <img
        v-if="coverPhoto"
        :src="coverPhoto"
        :alt="profile.name"
        class="w-full h-full object-cover select-none"
        style="-webkit-user-drag: none;"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-indigo-100 to-indigo-50" />
    </div>

    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-transparent pointer-events-none z-10" />

    <!-- Stamps -->
    <div class="absolute top-6 left-4 right-4 flex justify-between pointer-events-none z-20">
      <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-solid border-emerald-500 text-emerald-500 bg-emerald-500/12 -rotate-12 transition-opacity duration-150" :style="{ opacity: likeOpacity }">
        LIKE
      </div>
      <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-solid border-rose-500 text-rose-500 bg-rose-500/12 rotate-12 transition-opacity duration-150" :style="{ opacity: dislikeOpacity }">
        NOPE
      </div>
    </div>

    <!-- Profile Info -->
    <div class="relative z-20 text-white px-6 pt-5 pb-7 select-none pointer-events-none">
      <div class="flex items-baseline gap-2 mb-1">
        <h2 class="text-[1.8rem] font-extrabold m-0 leading-none drop-shadow-md">{{ profile.name }}</h2>
        <span v-if="profile.age" class="text-[1.4rem] font-normal opacity-90">{{ profile.age }}</span>
      </div>

      <div class="flex items-center gap-1 text-[0.85rem] opacity-90 mb-1.5">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
        </svg>
        <span>{{ distanceText }}</span>
      </div>

      <p v-if="profile.description || profile.bio" class="text-[0.85rem] leading-[1.45] opacity-85 m-0 line-clamp-2">
        {{ profile.description || profile.bio }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSwipe } from '@/composables/useSwipe.js'

const props = defineProps({
  profile: { type: Object, required: true },
})

const emit = defineEmits(['swiped'])

const {
  x, y, rotation, isDragging,
  likeOpacity, dislikeOpacity,
  handlers, triggerSwipe,
} = useSwipe((isLiked) => emit('swiped', isLiked ? 'like' : 'dislike'))

const cardStyle = computed(() => ({
  transform: `translate3d(${x.value}px, ${y.value}px, 0) rotate(${rotation.value}deg)`,
  width: 'min(94vw, 580px)',
  height: 'clamp(400px, calc(100svh - 260px), 620px)',
}))

const coverPhoto = computed(() =>
  props.profile.photos?.[0]?.url || props.profile.photo_url || null
)

const distanceText = computed(() => {
  const km = props.profile.distance
  if (km === undefined || km === null) return 'Distance unknown'
  if (km < 1) return 'Nearby'
  return `${Math.round(km)} km`
})

defineExpose({ triggerSwipe })
</script>

