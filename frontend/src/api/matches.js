/**
 * matches.js — Matches API calls
 * GET /api/matches
 */

import apiClient from './axios.js'

export const matchesApi = {
  /**
   * Get mutual matches for current user
   * @returns {Promise<{ matches: MutualLike[] }>} 
   */
  getAll() {
    return apiClient.get('/matches')
  },
}
