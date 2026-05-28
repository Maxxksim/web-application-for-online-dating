<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { useToast } from '@/composables/useToast.js'
import BaseInput from '@/components/BaseInput.vue'
import BaseButton from '@/components/BaseButton.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { toast } = useToast()

const isLogin = ref(true)

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const localError = ref('')

const isSubmitting = computed(() => authStore.isLoading)
const apiError = computed(() => authStore.error)

watch(isLogin, () => {
  authStore.clearError()
  localError.value = ''
  confirmPassword.value = ''
})

watch(
  () => route.query.mode,
  (mode) => {
    if (mode === 'register') isLogin.value = false
    if (mode === 'login') isLogin.value = true
  },
  { immediate: true }
)

watch(
  () => authStore.isAuthenticated,
  (isAuthed) => {
    if (!isAuthed) return
    const redirectTo = route.query.redirect ? String(route.query.redirect) : null
    router.push(redirectTo || { name: 'discover' })
  }
)

const submit = async () => {
  localError.value = ''
  authStore.clearError()

  if (!email.value || !password.value) {
    localError.value = 'Email and password are required.'
    return
  }

  if (!isLogin.value && password.value !== confirmPassword.value) {
    localError.value = 'Passwords do not match.'
    return
  }

  const ok = isLogin.value
    ? await authStore.login({ email: email.value, password: password.value })
    : await authStore.register({
        email: email.value,
        password: password.value,
        password_confirmation: confirmPassword.value,
      })

  if (ok) {
    const redirectTo = route.query.redirect ? String(route.query.redirect) : null
    router.push(redirectTo || { name: 'discover' })
  } else {
    toast.error(authStore.error || 'Authentication failed.')
  }
}

const startGoogleAuth = () => {
  authStore.openGoogleAuth()
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <RouterLink to="/" class="auth-back">← Back to Home</RouterLink>

      <div class="auth-card glass-panel animate-fade-in-up">
        <div class="auth-card__header">
          <p class="eyebrow">MatchFlow</p>
          <h2 class="auth-card__title">
            {{ isLogin ? 'Welcome back' : 'Create your account' }}
          </h2>
          <p class="auth-card__subtitle">Sign in or create an account in seconds.</p>
        </div>

        <form @submit.prevent="submit" class="auth-card__form" autocomplete="off">
          <BaseInput
            v-model="email"
            name="mf_email"
            type="email"
            label="Email"
            placeholder="name@matchflow.app"
            autocomplete="off"
            required
          />
          <BaseInput
            v-model="password"
            name="mf_password"
            type="password"
            label="Password"
            placeholder="At least 8 characters"
            autocomplete="new-password"
            required
          />
          <BaseInput
            v-if="!isLogin"
            v-model="confirmPassword"
            name="mf_password_confirm"
            type="password"
            label="Confirm Password"
            placeholder="Repeat your password"
            autocomplete="new-password"
            :error="localError"
            required
          />

          <BaseButton type="submit" variant="primary" size="lg" full :loading="isSubmitting">
            {{ isLogin ? 'Sign In' : 'Sign Up' }}
          </BaseButton>
        </form>

        <div v-if="localError || apiError" class="auth-card__error">
          {{ localError || apiError }}
        </div>

        <BaseButton variant="secondary" size="md" full class="auth-card__google" @click="startGoogleAuth">
          Continue with Google
        </BaseButton>

        <div class="divider-glow" />

        <div class="auth-card__toggle">
          <button @click="isLogin = !isLogin">
            {{ isLogin ? 'Need an account? Create one' : 'Already have an account? Sign in' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: var(--color-bg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-container {
  position: relative;
  width: 100%;
  max-width: 440px;
  padding: 20px;
}

.auth-back {
  display: block;
  margin-bottom: 16px;
  font-size: 0.85rem;
  color: var(--text-muted);
  transition: color var(--duration-fast) var(--ease-smooth);
}
.auth-back:hover {
  color: var(--text-primary);
}

.auth-card {
  padding: 32px;
}

.auth-card__header {
  text-align: center;
  margin-bottom: 28px;
}

.auth-card__title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  margin: 8px 0 6px;
  color: var(--text-primary);
}

.auth-card__subtitle {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}

.auth-card__form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.auth-card__error {
  margin-top: 12px;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  background: rgba(244, 63, 94, 0.08);
  border: 1px solid rgba(244, 63, 94, 0.2);
  color: var(--color-rose);
  font-size: 0.85rem;
}

.auth-card__google {
  margin-top: 12px;
}

.auth-card__toggle {
  text-align: center;
  padding-top: 16px;
}

.auth-card__toggle button {
  background: none;
  border: none;
  color: var(--color-accent);
  font-size: 0.85rem;
  cursor: pointer;
  transition: opacity var(--duration-fast);
}

.auth-card__toggle button:hover {
  opacity: 0.8;
}
</style>
