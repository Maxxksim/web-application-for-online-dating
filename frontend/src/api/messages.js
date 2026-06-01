/**
 * messages.js — Message API calls
 * GET  /api/chats/{chat}/messages
 * POST /api/chats/{recipient}/messages
 */

import apiClient from './axios.js'

export const messagesApi = {
  /**
   * Get messages for a chat
   * @param {number} chatId
   * @param {number} [page=1]
   * @returns {Promise<{ messages: { data: Message[], meta?, links? } }>}
   */
  getAll(chatId, page = 1) {
    return apiClient.get(`/chats/${chatId}/messages`, { params: { page } })
  },

  /**
   * Send a message to a recipient (chat auto-created by backend)
   * @param {number} recipientUserId
   * @param {string} text
   * @returns {Promise<{ message: string }>} 
   */
  send(recipientUserId, text) {
    return apiClient.post(`/chats/${recipientUserId}/messages`, { text })
  },

  /**
   * Mark all messages in a chat as read for the current user
   * PATCH /api/chats/{chat}/messages
   * @param {number} chatId
   */
  markAsRead(chatId) {
    return apiClient.patch(`/chats/${chatId}/messages`)
  },
}
