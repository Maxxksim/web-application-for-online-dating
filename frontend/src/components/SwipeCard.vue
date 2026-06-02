
<script setup>
import { computed, ref } from 'vue'
import { useSwipe } from '@/composables/useSwipe.js'
import { formatLabel } from '@/constants/profileOptions.js'

const props = defineProps({
  profile: { type: Object, required: true },
})

const emit = defineEmits(['swiped'])

const {
  x, y, rotation, isDragging,
  likeOpacity, dislikeOpacity,
  handlers: swipeHandlers, triggerSwipe,
} = useSwipe((isLiked) => emit('swiped', isLiked ? 'like' : 'dislike'))

const cardStyle = computed(() => ({
  transform: `translate3d(${x.value}px, ${y.value}px, 0) rotate(${rotation.value}deg)`,
  width: 'min(92vw, 420px)',
  height: 'clamp(520px, calc(100svh - 220px), 680px)',
}))

const photos = computed(() =>
  props.profile.photos?.filter(p => p?.url) || []
)

const photoIndex = ref(0)

const currentPhoto = computed(() =>
  photos.value[photoIndex.value]?.url || props.profile.photo_url || null
)

const profileInterests = computed(() =>
  Array.isArray(props.profile.interests) ? props.profile.interests : []
)

function nextPhoto() {
  if (photos.value.length > 1) photoIndex.value = (photoIndex.value + 1) % photos.value.length
}

function prevPhoto() {
  if (photos.value.length > 1) photoIndex.value = (photoIndex.value - 1 + photos.value.length) % photos.value.length
}

const distanceText = computed(() => {
  const km = props.profile.distance
  if (km === undefined || km === null) return 'Distance unknown'
  if (km < 1) return 'Nearby'
  return `${Math.round(km)} km`
})

function childrenLabel(value) {
  const map = { has: '👶 Has children', wants: 'Wants children', doesnt_want: 'No children', open: 'Open to children' }
  return map[value] || formatLabel(value)
}

function zodiacEmoji(sign) {
  const map = { aries: '♈', taurus: '♉', gemini: '♊', cancer: '♋', leo: '♌', virgo: '♍', libra: '♎', scorpio: '♏', sagittarius: '♐', capricorn: '♑', aquarius: '♒', pisces: '♓' }
  return map[sign] || ''
}

defineExpose({ triggerSwipe })
</script>
<template>
  <div
    class="relative cursor-grab touch-none origin-bottom rounded-[24px] border border-slate-200/50 shadow-xl bg-slate-100 overflow-hidden transition-[transform,box-shadow] duration-[350ms] ease-out will-change-transform"
    :class="{ 'cursor-grabbing': isDragging }"
    :style="cardStyle"
    v-on="swipeHandlers"
  >
    <!-- Photo -->
    <div class="absolute inset-0">
      <img
        v-if="currentPhoto"
        :src="currentPhoto"
        :alt="profile.name"
        class="w-full h-full object-cover object-top select-none transition-opacity duration-300"
        style="-webkit-user-drag: none;"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-slate-200 to-slate-100 flex items-center justify-center">
        <svg class="w-16 h-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </div>
    </div>

    <!-- Photo indicators -->
    <div v-if="photos.length > 1" class="absolute top-3 left-3 right-3 flex gap-1 z-20 pointer-events-none">
      <div
        v-for="(_, i) in photos"
        :key="i"
        class="h-[3px] flex-1 rounded-full transition-all duration-300"
        :class="i === photoIndex ? 'bg-white shadow-sm' : 'bg-white/40'"
      />
    </div>

    <!-- Arrow buttons -->
    <template v-if="photos.length > 1">
      <button
        class="absolute left-2 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-black/30 backdrop-blur-sm flex items-center justify-center text-white hover:bg-black/50 transition pointer-events-auto"
        @click.stop="prevPhoto"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>
      <button
        class="absolute right-2 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full bg-black/30 backdrop-blur-sm flex items-center justify-center text-white hover:bg-black/50 transition pointer-events-auto"
        @click.stop="nextPhoto"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 18l6-6-6-6"/>
        </svg>
      </button>
    </template>

    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent pointer-events-none z-10" />

    <!-- Stamps -->
    <div class="absolute top-6 left-4 right-4 flex justify-between pointer-events-none z-20">
      <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-emerald-500 text-emerald-500 bg-emerald-500/10 -rotate-12 transition-opacity duration-150" :style="{ opacity: likeOpacity }">
        LIKE
      </div>
      <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-rose-500 text-rose-500 bg-rose-500/10 rotate-12 transition-opacity duration-150" :style="{ opacity: dislikeOpacity }">
        NOPE
      </div>
    </div>

    <!-- Info block -->
    <div class="absolute bottom-0 left-0 right-0 z-20 px-5 pt-4 pb-5 text-white select-none pointer-events-none">

      <div class="flex items-baseline gap-2 mb-1">
        <h2 class="text-[1.75rem] font-extrabold m-0 leading-none drop-shadow-md">{{ profile.name }}</h2>
        <span v-if="profile.age" class="text-[1.3rem] font-light opacity-90">{{ profile.age }}</span>
      </div>

      <div class="flex items-center gap-2 mb-2 text-[0.82rem] opacity-85 flex-wrap">
        <span class="flex items-center gap-1">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
          </svg>
          {{ distanceText }}
        </span>
        <span v-if="profile.city" class="opacity-80">{{ profile.city }}</span>
        <span v-if="profile.dating_purpose" class="px-2 py-0.5 rounded-full bg-white/20 text-[0.72rem] font-medium">
          {{ formatLabel(profile.dating_purpose) }}
        </span>
      </div>

      <p v-if="profile.description || profile.bio" class="text-[0.83rem] leading-[1.45] opacity-80 m-0 mb-2.5 line-clamp-2">
        {{ profile.description || profile.bio }}
      </p>

      <div class="flex flex-wrap gap-1.5 mb-2">
        <span v-if="profile.height" class="tag-info">{{ profile.height }} cm</span>
        <span v-if="profile.weight" class="tag-info">{{ profile.weight }} kg</span>
        <span v-if="profile.body_type" class="tag-info">{{ formatLabel(profile.body_type) }}</span>
        <span v-if="profile.eye_color" class="tag-info">👁 {{ formatLabel(profile.eye_color) }}</span>
        <span v-if="profile.hair_color" class="tag-info">{{ formatLabel(profile.hair_color) }}</span>
        <span v-if="profile.zodiac_sign" class="tag-info">{{ zodiacEmoji(profile.zodiac_sign) }} {{ formatLabel(profile.zodiac_sign) }}</span>
        <span v-if="profile.children" class="tag-info">{{ childrenLabel(profile.children) }}</span>
        <span v-if="profile.smoking && profile.smoking !== 'never'" class="tag-info">🚬 {{ formatLabel(profile.smoking) }}</span>
        <span v-if="profile.drinking && profile.drinking !== 'never'" class="tag-info">🍷 {{ formatLabel(profile.drinking) }}</span>
        <span v-if="profile.exercise && profile.exercise !== 'never'" class="tag-info">💪 {{ formatLabel(profile.exercise) }}</span>
      </div>

      <div v-if="profileInterests.length" class="flex flex-wrap gap-1.5">
        <span
          v-for="interest in profileInterests.slice(0, 5)"
          :key="interest.id || interest.interest"
          class="tag-interest"
        >
          {{ formatLabel(interest.interest || interest) }}
        </span>
        <span v-if="profileInterests.length > 5" class="tag-interest opacity-60">
          +{{ profileInterests.length - 5 }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tag-info {
  @apply px-2 py-0.5 rounded-full bg-white/15 backdrop-blur-sm text-white text-[0.7rem] font-medium border border-white/20;
}
.tag-interest {
  @apply px-2 py-0.5 rounded-full bg-cyan-400/25 backdrop-blur-sm text-white text-[0.7rem] font-medium border border-cyan-300/30;
}
</style>