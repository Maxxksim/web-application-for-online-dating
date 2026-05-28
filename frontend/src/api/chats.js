/**
 * chats.js — Chat API calls
 * GET  /api/chats
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
}
