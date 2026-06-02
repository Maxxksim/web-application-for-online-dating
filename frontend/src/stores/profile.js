import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { profilesApi } from '@/api/profiles.js'
import { searchApi } from '@/api/search.js'
import { swipesApi } from '@/api/swipes.js'

export const useProfileStore = defineStore('profile', () => {
  const myProfile = ref(null)
  const isLoadingProfile = ref(false)
  const profileError = ref(null)
  const deck = ref([])
  const deckMeta = ref(null)
  const deckPage = ref(1)
  const isLoadingDeck = ref(false)
  const hydratedProfileIds = ref(new Set())
  const hydrationInFlight = new Set()
  const lastSwipedId = ref(null)
  const filters = ref(null)
  const isLoadingFilters = ref(false)
  const useAdditionalFilters = ref(false)
  const currentCard = computed(() => deck.value[0] || null)
  const hasCards = computed(() => deck.value.length > 0)
  const completionPct = computed(() => myProfile.value?.completion_percentage ?? 0)

  async function fetchMyProfile() {
    isLoadingProfile.value = true
    profileError.value = null
    try {
      const { data } = await profilesApi.getMe()
      myProfile.value = data.profile
      return { success: true }
    } catch (err) {
      profileError.value = 'Unable to load profile.'
      console.error(err)
      return { success: false }
    } finally {
      isLoadingProfile.value = false
    }
  }

  async function updateMyProfile(payload) {
    isLoadingProfile.value = true
    try {
      await profilesApi.updateMe(payload)
      await fetchMyProfile()
      return { success: true }
    } catch (err) {
      const errors = err.response?.data?.errors
      const message = errors
        ? Object.values(errors).flat().join(' ')
        : 'Unable to save profile.'
      return { success: false, message }
    } finally {
      isLoadingProfile.value = false
    }
  }

  async function toggleProfileVisibility(enable) {
    try {
      if (enable) {
        await profilesApi.enable()
      } else {
        await profilesApi.disable()
      }
      await fetchMyProfile()
      return { success: true }
    } catch (err) {
      const data = err.response?.data
      return {
        success: false,
        message: data?.message || 'Unable to update visibility.',
        missingFields: data?.missing_fields || [],
      }
    }
  }

  async function uploadPhotos(files) {
    try {
      await profilesApi.uploadPhotos(files)
      setTimeout(fetchMyProfile, 2000)
      return { success: true }
    } catch {
      return { success: false, message: 'Unable to upload photos.' }
    }
  }

  async function deletePhoto(photoId) {
    try {
      await profilesApi.deletePhoto(photoId)
      if (myProfile.value?.photos) {
        myProfile.value.photos = myProfile.value.photos.filter(p => p.id !== photoId)
      }
      return { success: true }
    } catch {
      return { success: false }
    }
  }

  async function fetchDeck(reset = false) {
    if (isLoadingDeck.value) return
    if (!reset && deckMeta.value && deckPage.value > deckMeta.value.last_page) return

    if (reset) {
      deck.value = []
      deckPage.value = 1
      deckMeta.value = null
      hydratedProfileIds.value = new Set()
    }

    isLoadingDeck.value = true
    try {
      const { data } = await searchApi.getProfiles(deckPage.value)
      deck.value.push(...data.profiles.data)
      deckMeta.value = data.profiles.meta
      deckPage.value++
      if (deck.value.length && (!deck.value[0].photos || deck.value[0].photos.length === 0)) {
        await hydrateDeckProfiles([deck.value[0]])
      }
      void hydrateDeckProfiles(data.profiles.data)
      return { success: true }
    } catch (err) {
      const status = err.response?.status
      const data = err.response?.data

      if (status === 404) {
        deck.value = []
      }

      console.error('Failed to fetch discovery deck', err)
      return {
        success: false,
        status,
        message: data?.message || 'Failed to fetch discovery deck',
        missingFields: data?.missing_fields || [],
      }
    } finally {
      isLoadingDeck.value = false
    }
  }

  async function addInterest(interest) {
    try {
      await profilesApi.addInterest(interest)
      await fetchMyProfile()
      return { success: true }
    } catch (err) {
      const data = err.response?.data
      const message = data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : data?.message || 'Failed to add interest.'
      return { success: false, message }
    }
  }

  async function deleteInterest(interestId) {
    try {
      await profilesApi.deleteInterest(interestId)
      if (myProfile.value?.interests) {
        myProfile.value.interests = myProfile.value.interests.filter((i) => i.id !== interestId)
      }
      return { success: true }
    } catch (err) {
      const message = err.response?.data?.message || 'Unable to delete interest.'
      return { success: false, message }
    }
  }

  async function swipeCard(userId, isLiked) {
    const swipedId = Number(userId)
    if (!Number.isInteger(swipedId) || swipedId <= 0) {
      console.error('Swipe failed: invalid user id', userId)
      return false
    }
    const [swiped] = deck.value.splice(0, 1)

    try {
      await swipesApi.swipe(swipedId, isLiked)
      lastSwipedId.value = swipedId
    } catch (err) {
      deck.value.unshift(swiped)
      console.error('Swipe failed', err)
      return false
    }
    if (deck.value.length < 3) {
      fetchDeck()
    }

    return true
  }

  async function rollbackSwipe() {
    if (!lastSwipedId.value) return { success: false, message: 'Nothing to undo.' }
    try {
      await swipesApi.rollbackSwipe(lastSwipedId.value)
      lastSwipedId.value = null
      await fetchDeck(true)
      return { success: true }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to undo swipe.'
      return { success: false, message }
    }
  }

  async function hydrateDeckProfiles(profiles = deck.value) {
    const targets = profiles
      .filter((profile) => profile?.id)
      .filter((profile) => {
        const hasPhotos = Array.isArray(profile.photos) && profile.photos.length > 0
        return !hasPhotos && !hydratedProfileIds.value.has(profile.id) && !hydrationInFlight.has(profile.id)
      })

    if (!targets.length) return

    targets.forEach((profile) => hydrationInFlight.add(profile.id))

    await Promise.allSettled(
      targets.map(async (profile) => {
        try {
          const { data } = await profilesApi.getById(profile.id)
          const details = data?.profile

          if (details) {
            if (Array.isArray(details.photos)) profile.photos = details.photos
            
            const fields = [
              'name', 'age', 'description', 'interests', 'height', 'weight', 
              'body_type', 'eye_color', 'hair_color', 'zodiac_sign', 'children', 
              'smoking', 'drinking', 'exercise', 'dating_purpose'
            ]
            
            for (const f of fields) {
              if (details[f] !== undefined && (profile[f] === undefined || profile[f] === null || (Array.isArray(profile[f]) && profile[f].length === 0))) {
                profile[f] = details[f]
              }
            }
          }

          hydratedProfileIds.value.add(profile.id)
        } catch (err) {
          console.warn('Failed to hydrate profile', err)
        } finally {
          hydrationInFlight.delete(profile.id)
        }
      })
    )
  }

  async function fetchFilters() {
    isLoadingFilters.value = true
    try {
      const { data } = await searchApi.getFilters()
      filters.value = data.filters
      useAdditionalFilters.value = Boolean(data.filters?.use_advanced_filters)
      return { success: true }
    } catch (err) {
      console.error(err)
      return { success: false }
    } finally {
      isLoadingFilters.value = false
    }
  }

  async function updateFilters(payload) {
    try {
      await searchApi.updateFilters(payload)
      filters.value = { ...filters.value, ...payload }
      await fetchDeck(true)
      return { success: true }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to update filters'
      return { success: false, message }
    }
  }

  async function setUseAdditionalFilters(value, { refresh = true } = {}) {
    useAdditionalFilters.value = Boolean(value)
    try {
      await searchApi.updateFilters({ use_advanced_filters: Boolean(value) })
      if (filters.value) filters.value.use_advanced_filters = Boolean(value)
    } catch (err) {
      console.error('Failed to persist advanced filters toggle', err)
    }
    if (refresh) fetchDeck(true)
  }

  return {
    myProfile, isLoadingProfile, profileError,
    deck, deckMeta, isLoadingDeck, lastSwipedId,
    filters, isLoadingFilters, useAdditionalFilters,
    currentCard, hasCards, completionPct,
    fetchMyProfile, updateMyProfile, toggleProfileVisibility,
    uploadPhotos, deletePhoto,
    addInterest, deleteInterest,
    fetchDeck, swipeCard, rollbackSwipe,
    fetchFilters, updateFilters,
    setUseAdditionalFilters,
  }
})