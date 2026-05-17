/**
 * stores/auth.js — Authentication state (Pinia)
 *
 * State  : token, user, isLoading, error
 * Actions: login, register, logout, initFromStorage
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.js'

export const useAuthStore = defineStore('auth', () => {
  // ── State ──
  const token    = ref(localStorage.getItem('auth_token') || null)
  const isLoading = ref(false)
  const error     = ref(null)

  // ── Getters ──
  const isAuthenticated = computed(() => !!token.value)

  // ── Helpers ──
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

  // ── Actions ──

  /**
   * Log in with email/password
   * @param {{ email: string, password: string }} credentials
   */
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

  /**
   * Register a new account
   * @param {{ email: string, password: string, password_confirmation: string }} payload
   */
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
        // Flatten Laravel validation errors to a single string
        error.value = Object.values(errors).flat().join(' ')
      } else {
        error.value = 'Registration failed. Please try again later.'
      }
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Log out current user
   */
  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Token might already be invalid — still clear locally
    } finally {
      setToken(null)
    }
  }

  /**
   * Handle Google OAuth callback token (called from popup postMessage)
   * @param {string} googleToken
   */
  function loginWithGoogleToken(googleToken) {
    setToken(googleToken)
  }

  /**
   * Open Google OAuth popup
   */
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
