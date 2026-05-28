<template>
  <div
    class="swipe-card"
    :class="{ 'swipe-card--dragging': isDragging }"
    :style="cardStyle"
    v-on="handlers"
  >
    <!-- Photo Layer -->
    <div class="swipe-card__photo">
      <img
        v-if="coverPhoto"
        :src="coverPhoto"
        :alt="profile.name"
      />
      <div v-else class="swipe-card__photo-fallback" />
    </div>

    <!-- Gradient Overlay -->
    <div class="swipe-card__overlay" />

    <!-- Stamps -->
    <div class="swipe-card__stamps">
      <div class="swipe-card__stamp swipe-card__stamp--like" :style="{ opacity: likeOpacity }">
        LIKE
      </div>
      <div class="swipe-card__stamp swipe-card__stamp--nope" :style="{ opacity: dislikeOpacity }">
        NOPE
      </div>
    </div>

    <!-- Profile Info -->
    <div class="swipe-card__info">
      <div class="swipe-card__name-row">
        <h2 class="swipe-card__name">{{ profile.name }}</h2>
        <span v-if="profile.age" class="swipe-card__age">{{ profile.age }}</span>
      </div>

      <div class="swipe-card__location">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
        </svg>
        <span>{{ distanceText }}</span>
      </div>

      <p v-if="profile.description || profile.bio" class="swipe-card__bio">
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

<style scoped>
.swipe-card {
  position: relative;
  overflow: hidden;
  cursor: grab;
  touch-action: none;
  transform-origin: bottom center;
  will-change: transform;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  border-radius: 20px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
  background: var(--color-surface);
  transition: box-shadow 0.25s var(--ease-smooth);
}

.swipe-card--dragging {
  cursor: grabbing;
}

.swipe-card__photo {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.swipe-card__photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  user-select: none;
  -webkit-user-drag: none;
}

.swipe-card__photo-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #e0e7ff, #f0f0ff);
}

.swipe-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.15) 50%, transparent 100%);
  pointer-events: none;
  z-index: 1;
}

.swipe-card__stamps {
  position: absolute;
  top: 24px;
  left: 16px;
  right: 16px;
  display: flex;
  justify-content: space-between;
  pointer-events: none;
  z-index: 2;
}

.swipe-card__stamp {
  font-weight: 800;
  font-size: 1.8rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  padding: 2px 8px;
  border-radius: 6px;
  border-width: 3px;
  border-style: solid;
  opacity: 0;
  transition: opacity 0.15s;
}

.swipe-card__stamp--like {
  color: var(--color-emerald);
  border-color: var(--color-emerald);
  background: rgba(16, 185, 129, 0.12);
  transform: rotate(-12deg);
}

.swipe-card__stamp--nope {
  color: var(--color-rose);
  border-color: var(--color-rose);
  background: rgba(244, 63, 94, 0.12);
  transform: rotate(12deg);
}

.swipe-card__info {
  position: relative;
  z-index: 2;
  color: #fff;
  padding: 20px 24px 28px;
  user-select: none;
  pointer-events: none;
}

.swipe-card__name-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 4px;
}

.swipe-card__name {
  font-size: 1.8rem;
  font-weight: 800;
  margin: 0;
  line-height: 1;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.swipe-card__age {
  font-size: 1.4rem;
  font-weight: 400;
  opacity: 0.9;
}

.swipe-card__location {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85rem;
  opacity: 0.9;
  margin-bottom: 6px;
}

.swipe-card__bio {
  font-size: 0.85rem;
  line-height: 1.45;
  opacity: 0.85;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
