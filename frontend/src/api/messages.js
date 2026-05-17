/**
 * messages.js — Message API calls
 * GET  /api/chats/{chat}/messages
 * POST /api/chats/{chat}/messages
 */

import apiClient from './axios.js'

export const messagesApi = {
  /**
   * Get messages for a chat
   * @param {number} chatId
   * @param {number} [page=1]
   * @returns {Promise<{ messages: PaginatedMessages }>} 
   */
  getAll(chatId, page = 1) {
    return apiClient.get(`/chats/${chatId}/messages`, { params: { page } })
  },

  /**
   * Send a message
   * @param {number} chatId
   * @param {string} text
   * @returns {Promise<{ message: string }>} 
   */
  send(chatId, text) {
    return apiClient.post(`/chats/${chatId}/messages`, { text })
  },
}
