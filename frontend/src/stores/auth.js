import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.js'

export const useAuthStore = defineStore('auth', () => {

  const token = ref(localStorage.getItem('auth_token') || null)
  const isLoading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)


  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth_token', newToken)
    } else {
      localStorage.removeItem('auth_token')
    }
  }

  function clearError() {
    error.value = null
  }

  async function login(credentials) {
    isLoading.value = true
    error.value = null
    try {
      const { data } = await authApi.login(credentials)
      setToken(data.token)
      return true
    } catch (err) {
      error.value = err.response?.data?.error || 'Login failed. Check your credentials.'
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function register(payload) {
    isLoading.value = true
    error.value = null
    try {
      const { data } = await authApi.register(payload)
      setToken(data.token)
      return true
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors) {

        error.value = Object.values(errors).flat().join(' ')
      } else {
        error.value = 'Registration failed. Please try again later.'
      }
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
    } finally {
      setToken(null)
    }
  }

  function loginWithGoogleToken(googleToken) {
    setToken(googleToken)
  }

  function openGoogleAuth() {
    const url = authApi.getGoogleRedirectUrl()
    const popup = window.open(url, 'google-auth', 'width=500,height=600')

    window.addEventListener('message', function handler(event) {
      if (event.data?.token) {
        loginWithGoogleToken(event.data.token)
        popup?.close()
        window.removeEventListener('message', handler)
      }
    })
  }

  return {
    token,
    isLoading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    clearError,
    loginWithGoogleToken,
    openGoogleAuth,
  }
})
