/**
 * likes.js — Likes API calls
 * GET /api/likes
 */

import apiClient from './axios.js'

export const likesApi = {
  /**
   * Get profiles who liked the current user
   * @returns {Promise<{ likes: ProfileData[] }>} 
   */
  getAll() {
    return apiClient.get('/likes')
  },
}
