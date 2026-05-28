/**
 * subscription.js — Subscription API calls
 * POST   /api/subscription/checkout
 * GET    /api/subscription/status
 * DELETE /api/subscription/cancel
 */

import apiClient from './axios.js'

const DEFAULT_PLAN = 'premium'

export const subscriptionApi = {
  checkout(plan = DEFAULT_PLAN) {
    return apiClient.post('/subscription/checkout', { plan })
  },

  status(plan = DEFAULT_PLAN) {
    return apiClient.get('/subscription/status', { params: { plan } })
  },

  cancel(plan = DEFAULT_PLAN) {
    return apiClient.delete('/subscription/cancel', { data: { plan } })
  },
}
