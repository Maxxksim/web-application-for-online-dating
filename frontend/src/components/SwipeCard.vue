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
}))

const photos = computed(() =>
  props.profile.photos?.filter(p => p?.url) || []
)

const photoIndex = ref(0)
const isExpanded = ref(false)

const currentPhoto = computed(() =>
  photos.value[photoIndex.value]?.url || props.profile.photo_url || null
)

const profileInterests = computed(() =>
  Array.isArray(props.profile.interests) ? props.profile.interests : []
)

function childrenLabel(value) {
  const map = { has: '👶 Has children', wants: 'Wants children', doesnt_want: 'No children', open: 'Open to children' }
  return map[value] || formatLabel(value)
}

function zodiacEmoji(sign) {
  const map = { aries: '♈', taurus: '♉', gemini: '♊', cancer: '♋', leo: '♌', virgo: '♍', libra: '♎', scorpio: '♏', sagittarius: '♐', capricorn: '♑', aquarius: '♒', pisces: '♓' }
  return map[sign] || ''
}

const profileDetailsList = computed(() => [
  props.profile.height ? { label: 'Height', value: `${props.profile.height} cm`, icon: '📏' } : null,
  props.profile.weight ? { label: 'Weight', value: `${props.profile.weight} kg`, icon: '⚖️' } : null,
  props.profile.body_type ? { label: 'Body type', value: formatLabel(props.profile.body_type), icon: '🧍' } : null,
  props.profile.eye_color ? { label: 'Eyes', value: formatLabel(props.profile.eye_color), icon: '👁️' } : null,
  props.profile.hair_color ? { label: 'Hair', value: formatLabel(props.profile.hair_color), icon: '💈' } : null,
  props.profile.zodiac_sign ? { label: 'Zodiac sign', value: `${zodiacEmoji(props.profile.zodiac_sign)} ${formatLabel(props.profile.zodiac_sign)}`, icon: '✨' } : null,
  props.profile.children ? { label: 'Children', value: childrenLabel(props.profile.children), icon: '👶' } : null,
  props.profile.smoking && props.profile.smoking !== 'never' ? { label: 'Smoking', value: formatLabel(props.profile.smoking), icon: '🚬' } : null,
  props.profile.drinking && props.profile.drinking !== 'never' ? { label: 'Drinking', value: formatLabel(props.profile.drinking), icon: '🍷' } : null,
  props.profile.exercise && props.profile.exercise !== 'never' ? { label: 'Exercise', value: formatLabel(props.profile.exercise), icon: '💪' } : null,
  props.profile.dating_purpose ? { label: 'Dating purpose', value: formatLabel(props.profile.dating_purpose), icon: '🎯' } : null,
].filter(Boolean))

function nextPhoto() {
  if (photos.value.length > 1) photoIndex.value = (photoIndex.value + 1) % photos.value.length
}

function prevPhoto() {
  if (photos.value.length > 1) photoIndex.value = (photoIndex.value - 1 + photos.value.length) % photos.value.length
}

function handlePhotoClick(event) {
  if (photos.value.length <= 1) return
  const rect = event.currentTarget.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  if (clickX < rect.width * 0.33) {
    prevPhoto()
  } else {
    nextPhoto()
  }
}

function handleModalAction(action) {
  isExpanded.value = false
  setTimeout(() => {
    triggerSwipe(action === 'like' ? 'right' : 'left')
  }, 100)
}

const distanceText = computed(() => {
  const km = props.profile.distance
  if (km === undefined || km === null) return 'Distance unknown'
  const displayKm = km < 1 ? 1 : Math.round(km)
  return `${displayKm} km away`
})

defineExpose({ triggerSwipe })
</script>

<template>
  <div
    class="relative h-[clamp(440px,calc(100svh-260px),620px)] w-[min(92vw,420px)] cursor-grab touch-none origin-bottom overflow-visible transition-[transform,box-shadow] duration-[350ms] ease-out will-change-transform md:grid md:h-[min(74svh,650px)] md:w-[min(92vw,860px)] md:grid-cols-[1fr_1fr] md:gap-5"
    :class="{ 'cursor-grabbing': isDragging }"
    :style="cardStyle"
    v-on="swipeHandlers"
  >

    <div class="hidden min-h-0 overflow-hidden rounded-[28px] border border-white/50 bg-slate-950 shadow-xl md:block">
      <div class="relative h-full w-full cursor-pointer" @click="handlePhotoClick">
        <img
          v-if="currentPhoto"
          :src="currentPhoto"
          :alt="profile.name"
          class="h-full w-full object-cover object-top select-none"
          style="-webkit-user-drag: none;"
        />
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-200 to-slate-100">
          <svg class="h-16 w-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
        </div>

        <div v-if="photos.length > 1" class="absolute left-4 right-4 top-4 z-20 flex gap-1.5">
          <div
            v-for="(_, i) in photos"
            :key="i"
            class="h-1 flex-1 rounded-full transition-all duration-300"
            :class="i === photoIndex ? 'bg-white shadow-sm' : 'bg-white/40'"
          />
        </div>

        <template v-if="photos.length > 1">
          <button class="no-lift absolute left-4 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition-colors hover:bg-black/50" @click.stop="prevPhoto">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 18l-6-6 6-6"/>
            </svg>
          </button>
          <button class="no-lift absolute right-4 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition-colors hover:bg-black/50" @click.stop="nextPhoto">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </button>
        </template>
      </div>
    </div>


    <div class="hidden min-h-0 overflow-hidden rounded-[28px] border border-white/50 bg-[linear-gradient(145deg,#1e293b_0%,#0891b2_48%,#db2777_100%)] p-6 text-white shadow-xl md:flex md:flex-col relative">
      <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_12%,rgba(255,255,255,0.25),transparent_28%),radial-gradient(circle_at_85%_90%,rgba(255,255,255,0.15),transparent_28%)]"></div>
      
      <div class="relative z-10 flex min-h-0 flex-1 flex-col">
        <div class="mb-4">
          <div class="flex items-end gap-2">

            <h2 class="m-0 text-[2.2rem] font-extrabold leading-tight pb-0.5">{{ profile.name }}</h2>
            <span v-if="profile.age" class="text-[1.5rem] font-medium leading-none text-white/88 pb-1.5">{{ profile.age }}</span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-2 text-[0.84rem] font-semibold text-white/82">
            <div class="flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 backdrop-blur-sm border border-white/10">
              <svg class="h-3.5 w-3.5 shrink-0 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ distanceText }}</span>
            </div>
            <span v-if="profile.city || profile.country" class="rounded-full bg-white/17 px-2.5 py-1 backdrop-blur">{{ [profile.city, profile.country].filter(Boolean).join(', ') }}</span>
            <span v-if="profile.dating_purpose" class="rounded-full bg-white/17 px-2.5 py-1 backdrop-blur">
              {{ formatLabel(profile.dating_purpose) }}
            </span>
          </div>
        </div>

        <div class="thin-scroll min-h-0 flex-1 overflow-y-auto pr-1">
          <div class="rounded-[22px] border border-white/15 bg-white/10 p-4 backdrop-blur-md shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]">
            <p class="m-0 text-[0.95rem] leading-relaxed text-white whitespace-pre-line">
              {{ profile.description || profile.bio || 'No bio provided.' }}
            </p>
          </div>

          <div v-if="profileDetailsList.length" class="mt-4 grid grid-cols-2 gap-2">
            <div v-for="detail in profileDetailsList" :key="detail.label" class="rounded-2xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-md">
              <p class="m-0 text-[0.66rem] font-semibold uppercase text-white/70">{{ detail.label }}</p>
              <p class="m-0 mt-0.5 text-[0.86rem] font-semibold text-white truncate">{{ detail.value }}</p>
            </div>
          </div>

          <div v-if="profileInterests.length" class="mt-4 flex flex-wrap gap-2">
            <span
              v-for="interest in profileInterests"
              :key="interest.id || interest.interest || interest"
              class="rounded-full border border-white/15 bg-white/14 px-3 py-1.5 text-[0.75rem] font-semibold text-white backdrop-blur-md"
            >
              {{ formatLabel(interest.interest || interest) }}
            </span>
          </div>
        </div>
      </div>
    </div>


    <div class="relative h-full w-full overflow-hidden rounded-[28px] border border-slate-200/50 bg-slate-100 shadow-xl md:hidden">
      <div class="absolute inset-0 cursor-pointer z-0" @click="handlePhotoClick">
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

      <div v-if="photos.length > 1" class="absolute top-3 left-3 right-3 flex gap-1.5 z-20 pointer-events-none">
        <div
          v-for="(_, i) in photos"
          :key="i"
          class="h-[3px] flex-1 rounded-full transition-all duration-300"
          :class="i === photoIndex ? 'bg-white shadow-[0_0_4px_rgba(255,255,255,0.5)]' : 'bg-white/35'"
        />
      </div>

      <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent pointer-events-none z-10" />

      <div class="absolute top-6 left-4 right-4 flex justify-between pointer-events-none z-20">
        <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-emerald-500 text-emerald-500 bg-emerald-500/10 -rotate-12 transition-opacity duration-150 will-change-opacity" :style="{ opacity: likeOpacity }">
          LIKE
        </div>
        <div class="font-extrabold text-[1.8rem] uppercase tracking-[0.15em] px-2 py-0.5 rounded-md border-[3px] border-rose-500 text-rose-500 bg-rose-500/10 rotate-12 transition-opacity duration-150 will-change-opacity" :style="{ opacity: dislikeOpacity }">
          NOPE
        </div>
      </div>

      <div class="absolute bottom-0 left-0 right-0 z-20 flex flex-col px-4 pb-4 pt-20 text-white pointer-events-none bg-gradient-to-t from-black/90 via-black/40 to-transparent rounded-b-[28px]">
        <div class="pointer-events-auto flex items-end justify-between gap-3">
          <div class="flex-1 min-w-0 flex flex-col gap-1.5 drop-shadow-md">

            <div class="flex items-baseline gap-2.5">

              <h2 class="text-[2.2rem] font-extrabold m-0 leading-tight pb-0.5 truncate flex-1 min-w-0">{{ profile.name }}</h2>
              <span v-if="profile.age" class="text-[1.5rem] font-medium opacity-90 shrink-0">{{ profile.age }}</span>
            </div>
            <div class="flex items-center flex-wrap gap-2 text-[0.85rem] opacity-90 font-medium truncate">
              <div class="flex items-center gap-1 shrink-0">
                <svg class="h-3.5 w-3.5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ distanceText }}</span>
              </div>
              <span v-if="profile.city || profile.country" class="rounded-full bg-white/20 px-2.5 py-0.5 backdrop-blur-sm truncate">{{ [profile.city, profile.country].filter(Boolean).join(', ') }}</span>
            </div>
          </div>
          <button @click.stop="isExpanded = true" class="w-10 h-10 shrink-0 bg-white text-rose-500 rounded-full flex items-center justify-center shadow-[0_4px_12px_rgba(0,0,0,0.3)] transition-transform hover:scale-105 active:scale-95" aria-label="Open profile">
            <svg class="w-5 h-5 translate-y-[1px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>


  <Teleport to="body">
  <transition name="slide-up">
    <div v-if="isExpanded" class="fixed inset-0 z-[9999] bg-slate-950 overflow-y-auto thin-scroll md:hidden">
      <div class="relative w-full h-[65svh] shrink-0">
        <img :src="currentPhoto" class="w-full h-full object-cover object-top select-none" style="-webkit-user-drag: none;" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent pointer-events-none"></div>
        
        <button class="absolute top-5 right-5 z-30 flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white shadow-md backdrop-blur-md transition-transform hover:scale-105 active:scale-95" @click.stop="isExpanded = false">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div class="absolute inset-0 z-10 flex">
           <div class="w-1/3 h-full cursor-pointer" @click="prevPhoto"></div>
           <div class="flex-1 h-full"></div>
           <div class="w-1/3 h-full cursor-pointer" @click="nextPhoto"></div>
        </div>

        <div v-if="photos.length > 1" class="absolute top-4 left-4 right-20 flex gap-1.5 z-20 pointer-events-none">
          <div v-for="(_, i) in photos" :key="i" class="h-[3px] flex-1 rounded-full transition-all duration-300" :class="i === photoIndex ? 'bg-white shadow-[0_0_4px_rgba(255,255,255,0.5)]' : 'bg-white/35'"></div>
        </div>
      </div>

      <div class="px-5 pt-3 pb-28 text-white relative z-20">
        <div class="flex justify-between items-start mb-6">
          <div class="flex-1 min-w-0">

            <div class="flex items-baseline gap-3 mb-1">
              <h2 class="text-[2.2rem] font-extrabold leading-tight pb-0.5 truncate flex-1 min-w-0">{{ profile.name }}</h2>
              <span v-if="profile.age" class="text-[1.5rem] font-medium text-white/90 shrink-0">{{ profile.age }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[0.85rem] font-medium text-white/85">
              <div class="flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-0.5">
                <svg class="h-3.5 w-3.5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ distanceText }}</span>
              </div>
              <span v-if="profile.city || profile.country" class="rounded-full bg-white/10 px-2.5 py-0.5">{{ [profile.city, profile.country].filter(Boolean).join(', ') }}</span>
              <span v-if="profile.dating_purpose" class="rounded-full bg-white/10 px-2.5 py-0.5">{{ formatLabel(profile.dating_purpose) }}</span>
            </div>
          </div>
        </div>

        <div v-if="profile.description || profile.bio" class="mb-6">
          <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">About</h3>
          <p class="text-[0.95rem] leading-[1.6] text-white/95 m-0 whitespace-pre-line">{{ profile.description || profile.bio }}</p>
        </div>

        <div v-if="profileDetailsList.length" class="mb-6">
          <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">Details</h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="detail in profileDetailsList" :key="detail.label" class="rounded-[14px] border border-white/10 bg-white/5 px-3 py-2 text-[0.8rem] text-white/90 font-medium backdrop-blur-sm">
              {{ detail.icon }} {{ detail.value }}
            </span>
          </div>
        </div>

        <div v-if="profileInterests.length" class="mb-6">
          <h3 class="text-[0.7rem] font-bold uppercase tracking-widest text-white/50 mb-2.5">Interests</h3>
          <div class="flex flex-wrap gap-2">
            <span v-for="interest in profileInterests" :key="interest.id || interest.interest || interest" class="rounded-full border border-cyan-400/20 bg-cyan-500/10 px-3 py-1.5 text-[0.8rem] text-white/90 font-medium backdrop-blur-sm">
              {{ formatLabel(interest.interest || interest) }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="fixed bottom-6 left-0 right-0 z-30 flex items-center justify-center w-full px-5 pointer-events-none">
        <div class="pointer-events-auto flex justify-center gap-4 w-full max-w-[300px]">
          <button @click="handleModalAction('dislike')" class="flex h-[60px] w-[60px] items-center justify-center rounded-full bg-slate-900 border border-rose-500/30 text-rose-500 shadow-lg transition-transform hover:scale-105 active:scale-95">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
          </button>
          <button @click="handleModalAction('like')" class="flex h-[60px] w-[60px] items-center justify-center rounded-full bg-slate-900 border border-emerald-500/30 text-emerald-500 shadow-lg transition-transform hover:scale-105 active:scale-95">
            <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21s-6.7-4.2-9.3-8.2C.4 9.2 2.2 5 6.3 5c2.2 0 3.7 1.2 4.5 2.4C11.6 6.2 13.1 5 15.3 5c4.1 0 5.9 4.2 3.6 7.8C18.3 16.8 12 21 12 21Z" /></svg>
          </button>
        </div>
      </div>
    </div>
  </transition>
  </Teleport>
</template>

<style scoped>
.tag-info {
  @apply px-2.5 py-1.5 rounded-full bg-white/10 backdrop-blur-sm text-white text-[0.73rem] font-medium border border-white/15 transition-colors hover:bg-white/20;
}
.tag-interest {
  @apply px-2.5 py-1.5 rounded-full bg-cyan-400/20 backdrop-blur-sm text-white text-[0.73rem] font-medium border border-cyan-300/25;
}
.thin-scroll::-webkit-scrollbar {
  width: 4px;
}
.thin-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.thin-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
}
</style>