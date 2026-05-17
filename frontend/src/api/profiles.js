/**
 * profiles.js — Profile management API calls
 * GET   /api/profiles/me
 * GET   /api/profiles/{id}
 * PATCH /api/profiles/me
 * PATCH /api/profiles/enable
 * PATCH /api/profiles/disable
 * POST  /api/profiles/photos
 * DELETE /api/profiles/photos/{id}
 */

import apiClient from './axios.js'

export const profilesApi = {
  /**
   * Get authenticated user's own profile
   * @returns {Promise<{ profile: ProfileData }>}
   */
  getMe() {
    return apiClient.get('/profiles/me')
  },

  /**
   * Get any user's profile by ID
   * @param {number} profileId
   * @returns {Promise<{ profile: ProfileData }>}
   */
  getById(profileId) {
    return apiClient.get(`/profiles/${profileId}`)
  },

  /**
   * Update personal profile data
   * @param {{ name: string, date_of_birth: string, gender: string, description?: string }} payload
   * @returns {Promise<{ message: string }>}
   */
  updateMe(payload) {
    return apiClient.patch('/profiles/me', payload)
  },

  /**
   * Enable profile visibility in search
   * @returns {Promise<{ message: string } | { message: string, missing_fields: string[] }>}
   */
  enable() {
    return apiClient.patch('/profiles/enable')
  },

  /**
   * Disable profile visibility in search
   * @returns {Promise<{ message: string }>}
   */
  disable() {
    return apiClient.patch('/profiles/disable')
  },

  /**
   * Upload profile photos (multipart/form-data)
   * @param {File[]} files
   * @returns {Promise<{ message: string }>}
   */
  uploadPhotos(files) {
    const formData = new FormData()
    files.forEach((file) => formData.append('photos[]', file))
    return apiClient.post('/profiles/photos', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  /**
   * Delete a specific photo
   * @param {number} photoId
   * @returns {Promise<void>}
   */
  deletePhoto(photoId) {
    return apiClient.delete(`/profiles/photos/${photoId}`)
  },
}
