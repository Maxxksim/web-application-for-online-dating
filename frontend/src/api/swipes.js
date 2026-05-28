/**
 * swipes.js — Swipe actions API
 * POST   /api/swipes/{target_user_id}
 * DELETE /api/swipes/{target_user_id}
 */

import apiClient from './axios.js'

export const swipesApi = {
  /**
   * Send a like or dislike
   * @param {number} targetUserId
   * @param {boolean} isLiked
   * @returns {Promise<void>}
   */
  swipe(targetUserId, isLiked) {
    const swipedId = Number(targetUserId)
    if (!Number.isInteger(swipedId)) {
      throw new Error('Invalid swipe target id')
    }

    return apiClient.post(`/swipes/${swipedId}`, { is_liked: isLiked })
  },

  /**
   * Rollback (undo) a previous swipe
   * @param {number} targetUserId
   * @returns {Promise<void>}
   */
  rollbackSwipe(targetUserId) {
    const swipedId = Number(targetUserId)
    if (!Number.isInteger(swipedId)) {
      throw new Error('Invalid swipe target id')
    }

    return apiClient.delete(`/swipes/${swipedId}`)
  },
}
