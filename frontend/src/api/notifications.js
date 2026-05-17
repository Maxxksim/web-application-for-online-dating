/**
 * notifications.js — Notifications API
 * GET   /api/notifications
 * PATCH /api/notifications/{id}
 */

import apiClient from './axios.js'

export const notificationsApi = {
  /**
   * Get all notifications for current user
   * @returns {Promise<{ notifications: Notification[] }>}
   */
  getAll() {
    return apiClient.get('/notifications')
  },

  /**
   * Mark a notification as read
   * @param {string} id  — UUID
   * @returns {Promise<void>}
   */
  markAsRead(id) {
    return apiClient.patch(`/notifications/${id}`)
  },
}
