<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()

const isAuthed = computed(() => authStore.isAuthenticated)

const goAuth = (mode = 'login') => router.push({ name: 'auth', query: { mode } })
const goDiscover = () => router.push({ name: 'discover' })

</script>

<template>
  <div class="home">
    <!-- Ambient background -->
    <div class="home-bg" aria-hidden="true">
      <div class="home-orb home-orb--1 animate-float" />
      <div class="home-orb home-orb--2" />
      <div class="home-orb home-orb--3 animate-float" style="animation-delay: -3s" />
      <div class="app-grid" />
    </div>

    <!-- ═══ HERO ═══ -->
    <section class="hero">
      <div class="hero__content animate-fade-in-up">
        <p class="eyebrow">MatchFlow</p>
        <h1 class="hero__title">
          Where Connections<br />
          Become <span class="gradient-text">Real</span>
        </h1>
        <p class="hero__subtitle">
          Find someone who gets you — starting now.
        </p>
        <div class="hero__actions">
          <BaseButton v-if="!isAuthed" variant="primary" size="lg" @click="goAuth('register')">
            Get Started
          </BaseButton>
          <BaseButton v-if="!isAuthed" variant="secondary" size="lg" @click="goAuth('login')">
            Sign In
          </BaseButton>
          <BaseButton v-if="isAuthed" variant="primary" size="lg" @click="goDiscover">
            Go to Discover
          </BaseButton>
        </div>
      </div>

      <!-- Floating cards decoration -->
      <div class="hero__visual animate-fade-in-up" style="animation-delay: 0.2s" aria-hidden="true">
        <div class="hero-card hero-card--1">
          <div class="hero-card__avatar">💜</div>
          <div class="hero-card__text">
            <span class="hero-card__name">Sara, 24</span>
            <span class="hero-card__loc">2 km away</span>
          </div>
        </div>
        <div class="hero-card hero-card--2">
          <div class="hero-card__avatar">🧡</div>
          <div class="hero-card__text">
            <span class="hero-card__name">Max, 27</span>
            <span class="hero-card__loc">5 km away</span>
          </div>
        </div>
        <div class="hero-card hero-card--3">
          <div class="hero-card__stamp">MATCH</div>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
/* ── Layout ── */
.home {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
}

.home-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.home-orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(100px);
  opacity: 0.35;
  pointer-events: none;
}
.home-orb--1 {
  width: 600px; height: 600px;
  background: rgba(139, 92, 246, 0.7);
  top: -200px; right: -200px;
}
.home-orb--2 {
  width: 500px; height: 500px;
  background: rgba(217, 70, 239, 0.55);
  bottom: -180px; left: -180px;
}
.home-orb--3 {
  width: 400px; height: 400px;
  background: rgba(251, 113, 133, 0.4);
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
}

section, footer {
  position: relative;
  z-index: 1;
}

/* ── Hero ── */
.hero {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 60px;
  min-height: 100vh;
  padding: 80px 24px 60px;
  max-width: 1200px;
  margin: 0 auto;
}

.hero__content {
  flex: 1;
  max-width: 580px;
}

.hero__title {
  font-family: var(--font-display);
  font-size: clamp(2.5rem, 5vw, 4.2rem);
  font-weight: 800;
  line-height: 1.1;
  color: var(--text-primary);
  margin: 12px 0 24px;
}

.hero__subtitle {
  font-size: clamp(1rem, 1.5vw, 1.15rem);
  line-height: 1.7;
  color: var(--text-secondary);
  max-width: 460px;
  margin-bottom: 36px;
}

.hero__actions {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

/* Floating visual decoration */
.hero__visual {
  position: relative;
  width: 320px;
  height: 400px;
  flex-shrink: 0;
}

.hero-card {
  position: absolute;
  border-radius: var(--radius-lg);
  border: 1px solid var(--glass-border);
  backdrop-filter: blur(16px);
  box-shadow: var(--shadow-soft);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.hero-card--1 {
  background: rgba(17, 8, 33, 0.75);
  top: 30px; left: 0;
  animation: floatSoft 6s ease-in-out infinite;
}
.hero-card--2 {
  background: rgba(17, 8, 33, 0.75);
  top: 140px; right: 0;
  animation: floatSoft 6s ease-in-out infinite;
  animation-delay: -2s;
}
.hero-card--3 {
  background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(217,70,239,0.25));
  border-color: rgba(217, 70, 239, 0.4);
  bottom: 60px; left: 50%; transform: translateX(-50%);
  animation: floatSoft 6s ease-in-out infinite;
  animation-delay: -4s;
  padding: 14px 32px;
}

.hero-card__avatar {
  font-size: 2rem;
  width: 48px; height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.3);
}

.hero-card__text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.hero-card__name {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--text-primary);
}
.hero-card__loc {
  font-size: 0.78rem;
  color: var(--text-muted);
}

.hero-card__stamp {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 0.3em;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
/* ── Responsive ── */
@media (max-width: 900px) {
  .hero {
    flex-direction: column;
    text-align: center;
    padding-top: 100px;
    min-height: auto;
  }
  .hero__subtitle {
    margin-left: auto;
    margin-right: auto;
  }
  .hero__actions {
    justify-content: center;
  }
  .hero__visual {
    width: 280px;
    height: 340px;
  }
}

@media (max-width: 480px) {
  .hero__visual {
    display: none;
  }
}
</style>
