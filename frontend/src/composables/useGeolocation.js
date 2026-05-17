/**
 * composables/useGeolocation.js
 * Requests browser geolocation and syncs with backend via searchApi.
 */

import { ref } from 'vue'
import { searchApi } from '@/api/search.js'

export function useGeolocation() {
  const isLocating = ref(false)
  const locationError = ref(null)

  async function syncLocation() {
    if (!navigator.geolocation) {
      locationError.value = 'Geolocation is not supported by your browser.'
      return
    }

    isLocating.value = true
    locationError.value = null

    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        async ({ coords }) => {
          try {
            await searchApi.updateLocation({
              latitude:  coords.latitude,
              longitude: coords.longitude,
            })
          } catch (err) {
            console.warn('Location sync failed', err)
          } finally {
            isLocating.value = false
            resolve()
          }
        },
        (err) => {
          locationError.value = 'Unable to retrieve your location.'
          isLocating.value = false
          resolve()
        },
        { timeout: 8000, maximumAge: 5 * 60 * 1000 }
      )
    })
  }

  return { isLocating, locationError, syncLocation }
}
