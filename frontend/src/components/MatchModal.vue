<template>
  <div class="match-modal">
    <div class="match-modal__content animate-match-reveal">
      <div class="match-modal__header">
        <p class="match-modal__label">Mutual Like</p>
        <h2 class="match-modal__title">It's a Match!</h2>
      </div>

      <div class="match-modal__avatars">
        <div class="match-modal__avatar">
          <img v-if="myPhoto" :src="myPhoto" alt="You" />
          <span v-else>Me</span>
        </div>
        <div class="match-modal__avatar">
          <img v-if="theirPhoto" :src="theirPhoto" alt="Match" />
          <span v-else>{{ theirName[0] }}</span>
        </div>
      </div>

      <p class="match-modal__text">
        You liked <strong>{{ theirName }}</strong>
      </p>

      <button class="match-modal__btn" @click="close">Continue</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  matchData: { type: Object, required: true },
})

const emit = defineEmits(['close'])

const theirName = computed(() => props.matchData.user.name || 'User')
const theirPhoto = computed(() => props.matchData.user.photos?.[0]?.url || props.matchData.user.photo_url)
const myPhoto = computed(() => props.matchData.match?.photos?.[0]?.url || null)

const close = () => emit('close')
</script>

<style scoped>
.match-modal {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
}

.match-modal__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 40px 32px;
  max-width: 400px;
  width: 100%;
}

.match-modal__header {
  margin-bottom: 32px;
}

.match-modal__label {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--color-rose);
  margin: 0 0 6px;
}

.match-modal__title {
  font-family: var(--font-display);
  font-size: 2.2rem;
  font-weight: 800;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin: 0;
}

.match-modal__avatars {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
}

.match-modal__avatar {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid var(--border-color);
  box-shadow: var(--shadow-md);
}

.match-modal__avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.match-modal__avatar span {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-accent-muted);
  color: var(--color-accent);
  font-size: 1.8rem;
  font-weight: 700;
}

.match-modal__text {
  font-size: 1rem;
  color: var(--text-secondary);
  margin: 0 0 32px;
}

.match-modal__text strong {
  color: var(--text-primary);
  font-weight: 700;
}

.match-modal__btn {
  width: 100%;
  max-width: 280px;
  padding: 14px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.95rem;
  background: var(--color-accent);
  color: #fff;
  border: none;
  cursor: pointer;
  transition: background var(--duration-fast), transform var(--duration-fast);
}

.match-modal__btn:hover {
  background: var(--color-accent-light);
}

.match-modal__btn:active {
  transform: scale(0.97);
}

@keyframes match-reveal {
  0% { opacity: 0; transform: scale(0.92); }
  60% { opacity: 1; transform: scale(1.03); }
  100% { opacity: 1; transform: scale(1); }
}

.animate-match-reveal {
  animation: match-reveal 0.45s ease-out forwards;
}
</style>
