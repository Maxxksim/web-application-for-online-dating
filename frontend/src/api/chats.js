/**
 * chats.js — Chat API calls
 * GET  /api/chats
 * PUT  /api/chats/{user}
 */

import apiClient from './axios.js'

export const chatsApi = {
  /**
   * Get chats for current user
   * @returns {Promise<{ chats: Chat[] }>} 
   */
  getAll() {
    return apiClient.get('/chats')
  },

  /**
   * Create or get chat with a user
   * @param {number} userId
   * @returns {Promise<{ chat: Chat }>} 
   */
  firstOrCreate(userId) {
    return apiClient.put(`/chats/${userId}`)
  },
}
