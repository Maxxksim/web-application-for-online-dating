/**
 * search.js — Search and geolocation API calls
 * PATCH /api/location
 * GET   /api/search/filters
 * PATCH /api/search/filters
 * GET   /api/search/profiles
 */

import apiClient from './axios.js'

export const searchApi = {
  /**
   * Update user geolocation (PostGIS)
   * @param {{ latitude: number, longitude: number }} coords
   * @returns {Promise<void>}
   */
  updateLocation(coords) {
    return apiClient.patch('/location', coords)
  },

  /**
   * Get current search filters
   * @returns {Promise<{ filters: SearchFilters }>}
   */
  getFilters() {
    return apiClient.get('/search/filters')
  },

  /**
   * Update search filters
   * @param {Partial<SearchFilters>} filters
   * @returns {Promise<void>}
   */
  updateFilters(filters) {
    return apiClient.patch('/search/filters', filters)
  },

  /**
   * Get paginated list of search results
   * @param {number} [page=1]
   * @returns {Promise<{ profiles: PaginatedProfiles }>}
   */
  getProfiles(page = 1) {
    return apiClient.get('/search/profiles', {
      params: { page },
    })
  },
}
