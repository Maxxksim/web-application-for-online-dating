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

      return {
        success: true,
        processing: true
      }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Unable to upload photos.'
      }
    }
  }

  async function deletePhoto(photoId) {
    try {
      await profilesApi.deletePhoto(photoId)

      await fetchMyProfile()

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
      if (err.response?.status === 404) {
        deck.value = []
      }

      return {
        success: false,
        message: err.response?.data?.message || 'Failed to fetch discovery deck',
        missingFields: err.response?.data?.missing_fields || [],
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

      myProfile.value.interests =
        myProfile.value.interests?.filter(i => i.id !== interestId) || []

      return { success: true }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Unable to delete interest.'
      }
    }
  }

  async function swipeCard(userId, isLiked) {
    const swipedId = Number(userId)
    if (!Number.isInteger(swipedId)) return false

    const [swiped] = deck.value.splice(0, 1)

    try {
      await swipesApi.swipe(swipedId, isLiked)
      lastSwipedId.value = swipedId
    } catch (err) {
      deck.value.unshift(swiped)
      return false
    }

    if (deck.value.length < 3) {
      fetchDeck()
    }

    return true
  }

  async function rollbackSwipe() {
    if (!lastSwipedId.value) return { success: false }

    try {
      await swipesApi.rollbackSwipe(lastSwipedId.value)
      lastSwipedId.value = null
      await fetchDeck(true)
      return { success: true }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to undo swipe.'
      }
    }
  }

  async function hydrateDeckProfiles(profiles = deck.value) {
    const targets = profiles.filter(p =>
      p?.id &&
      !hydratedProfileIds.value.has(p.id) &&
      !hydrationInFlight.has(p.id)
    )

    targets.forEach(p => hydrationInFlight.add(p.id))

    await Promise.allSettled(
      targets.map(async (profile) => {
        try {
          const { data } = await profilesApi.getById(profile.id)
          const details = data?.profile

          if (details) {
            profile.photos = details.photos || profile.photos

            Object.assign(profile, {
              name: details.name ?? profile.name,
              age: details.age ?? profile.age,
              description: details.description ?? profile.description,
            })
          }

          hydratedProfileIds.value.add(profile.id)
        } catch { }
        finally {
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
      return {
        success: false,
        message: err.response?.data?.message || 'Failed to update filters'
      }
    }
  }

  async function setUseAdditionalFilters(value, { refresh = true } = {}) {
    useAdditionalFilters.value = Boolean(value)

    try {
      await searchApi.updateFilters({ use_advanced_filters: Boolean(value) })
    } catch { }

    if (refresh) fetchDeck(true)
  }

  return {
    myProfile,
    isLoadingProfile,
    profileError,
    deck,
    deckMeta,
    isLoadingDeck,
    lastSwipedId,
    filters,
    isLoadingFilters,
    useAdditionalFilters,
    currentCard,
    hasCards,
    completionPct,
    fetchMyProfile,
    updateMyProfile,
    toggleProfileVisibility,
    uploadPhotos,
    deletePhoto,
    addInterest,
    deleteInterest,
    fetchDeck,
    swipeCard,
    rollbackSwipe,
    fetchFilters,
    updateFilters,
    setUseAdditionalFilters,
  }
})