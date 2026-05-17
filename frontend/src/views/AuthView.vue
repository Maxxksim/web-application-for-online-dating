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
    <!-- Ambient background -->
    <div class="auth-bg" aria-hidden="true">
      <div class="app-orb app-orb--one animate-float" />
      <div class="app-orb app-orb--two" />
      <div class="app-orb app-orb--three" />
      <div class="app-grid" />
    </div>

    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10 relative z-[1]">
      <RouterLink to="/" class="auth-back">← Back to Home</RouterLink>

      <div class="w-full max-w-md glass-panel p-8 md:p-10 animate-fade-in-up">
        <div class="text-center mb-8">
          <p class="eyebrow">MatchFlow</p>
          <h2 class="page-title text-3xl mt-2">
            {{ isLogin ? 'Welcome back' : 'Create your account' }}
          </h2>
          <p class="page-subtitle">Sign in or create an account in seconds.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5" autocomplete="off">
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

        <div v-if="localError || apiError" class="glass-panel glass-panel--tight px-4 py-3 text-sm text-rose-200 border border-rose-400/30 mt-4">
          {{ localError || apiError }}
        </div>

        <BaseButton variant="secondary" size="md" full class="mt-4" @click="startGoogleAuth">
          Continue with Google
        </BaseButton>

        <div class="divider-glow my-6" />

        <div class="text-center">
          <button @click="isLogin = !isLogin" class="text-sm text-violet-200 hover:text-white transition">
            {{ isLogin ? 'Need an account? Create one' : 'Already have an account? Sign in' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  position: relative;
  min-height: 100vh;
  background: var(--gradient-app);
  overflow: hidden;
}

.auth-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.auth-back {
  position: absolute;
  top: 24px;
  left: 24px;
  font-size: 0.85rem;
  color: var(--text-muted);
  transition: color var(--duration-fast) var(--ease-smooth);
  z-index: 10;
}
.auth-back:hover {
  color: var(--text-primary);
}
</style>
