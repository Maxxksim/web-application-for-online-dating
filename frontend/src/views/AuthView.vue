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
  } 
}

const startGoogleAuth = () => {
  authStore.openGoogleAuth()
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-5 pt-6 pb-20">
    <div class="w-full max-w-[420px]">
      <RouterLink to="/" class="block mb-[14px] text-[0.84rem] font-medium text-slate-500 no-underline transition-colors duration-[160ms] hover:text-slate-900">← Back to home</RouterLink>

      <div class="rounded-[32px] border border-[rgba(226,232,240,0.75)] bg-[rgba(255,255,255,0.72)] shadow-[0_4px_16px_rgba(15,23,42,0.06),0_1px_4px_rgba(15,23,42,0.04)] backdrop-blur-[20px] -webkit-backdrop-blur-[20px] px-7 py-8 animate-fade-in-up">
        <div class="text-center mb-6">
          <p class="text-[0.75rem] font-bold uppercase tracking-[0.15em] text-cyan-500 m-0 mb-1.5">MatchFlow</p>
          <h2 class="font-display text-[1.45rem] font-bold mt-2 mb-1.5 text-slate-900">
            {{ isLogin ? 'Welcome back' : 'Create your account' }}
          </h2>
          <p class="text-[0.88rem] text-slate-700 m-0">
            {{ isLogin ? 'Sign in to continue.' : 'Join and start connecting.' }}
          </p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-[14px]" autocomplete="off">
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
            required
          />

          <BaseButton type="submit" variant="primary" size="lg" full :loading="isSubmitting">
            {{ isLogin ? 'Sign In' : 'Sign Up' }}
          </BaseButton>
        </form>

        <div v-if="localError || apiError" class="mt-3 px-[14px] py-[10px] rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-[0.84rem]">
          {{ localError || apiError }}
        </div>

        <BaseButton variant="secondary" size="md" full class="mt-3" @click="startGoogleAuth">
          <svg class="w-5 h-5 mr-2 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A11.96 11.96 0 0 0 1 12c0 1.94.46 3.77 1.18 5.07l3.66-2.98z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Continue with Google
        </BaseButton>

        <div class="mt-4 h-[1px] w-full bg-[linear-gradient(90deg,rgba(14,165,233,0)_0%,rgba(14,165,233,0.3)_45%,rgba(14,165,233,0)_100%)]" />

        <div class="text-center pt-3">
          <button @click="isLogin = !isLogin" class="bg-transparent border-none text-cyan-500 text-[0.84rem] font-medium cursor-pointer transition-opacity duration-[160ms] hover:opacity-75">
            {{ isLogin ? 'Need an account? Create one' : 'Already have an account? Sign in' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

