/**
 * auth.js — Authentication API calls
 * POST /api/auth/register
 * POST /api/auth/login
 * DELETE /api/auth/logout
 * GET  /api/auth/google/redirect
 */

import apiClient from './axios.js'

export const authApi = {
  /**
   * Register a new user
   * @param {{ email: string, password: string, password_confirmation: string }} payload
   * @returns {Promise<{ token: string }>}
   */
  register(payload) {
    return apiClient.post('/auth/register', payload)
  },

  /**
   * Log in with credentials
   * @param {{ email: string, password: string }} payload
   * @returns {Promise<{ token: string }>}
   */
  login(payload) {
    return apiClient.post('/auth/login', payload)
  },

  /**
   * Invalidate current token
   * @returns {Promise<{ message: string }>}
   */
  logout() {
    return apiClient.delete('/auth/logout')
  },

  /**
   * Get Google OAuth redirect URL
   * @returns {string}
   */
  getGoogleRedirectUrl() {
    const base = import.meta.env.VITE_API_BASE_URL || '/api'
    return `${base}/auth/google/redirect`
  },
}
