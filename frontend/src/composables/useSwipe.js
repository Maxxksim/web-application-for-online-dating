
import { ref, computed } from 'vue'

const SWIPE_THRESHOLD  = 80
const ROTATE_FACTOR    = 0.08
const VELOCITY_BOOST   = 1.4

export function useSwipe(onSwipe) {
  const x         = ref(0)
  const y         = ref(0)
  const isDragging = ref(false)


  const direction = computed(() => {
    if (Math.abs(x.value) < 10) return null
    return x.value > 0 ? 'right' : 'left'
  })

  const rotation = computed(() => x.value * ROTATE_FACTOR)


  const likeOpacity    = computed(() => Math.min(1, Math.max(0,  x.value / SWIPE_THRESHOLD)))
  const dislikeOpacity = computed(() => Math.min(1, Math.max(0, -x.value / SWIPE_THRESHOLD)))


  let startX   = 0
  let startY   = 0
  let startTime = 0

  function onDragStart(event) {
    isDragging.value = true
    startTime = Date.now()

    const point = event.touches?.[0] ?? event
    startX = point.clientX - x.value
    startY = point.clientY - y.value

    event.currentTarget?.style.setProperty('transition', 'none')
  }

  function onDragMove(event) {
    if (!isDragging.value) return
    event.preventDefault?.()

    const point = event.touches?.[0] ?? event
    x.value = point.clientX - startX
    y.value = point.clientY - startY
  }

  function onDragEnd(event) {
    if (!isDragging.value) return
    isDragging.value = false

    event.currentTarget?.style.removeProperty('transition')

    const elapsed  = Date.now() - startTime
    const velocity = Math.abs(x.value) / elapsed
    const boosted  = velocity > 0.5 ? x.value * VELOCITY_BOOST : x.value

    if (Math.abs(boosted) >= SWIPE_THRESHOLD) {
      const isLiked = boosted > 0
      x.value = isLiked ? window.innerWidth : -window.innerWidth
      y.value = y.value * 1.5
      setTimeout(() => {
        reset()
        onSwipe?.(isLiked)
      }, 350)
    } else {

      reset()
    }
  }

  function reset() {
    x.value = 0
    y.value = 0
  }

  function triggerSwipe(isLiked) {
    x.value = isLiked ? window.innerWidth * 0.6 : -window.innerWidth * 0.6
    setTimeout(() => {
      reset()
      onSwipe?.(isLiked)
    }, 350)
  }

  return {
    x,
    y,
    rotation,
    direction,
    isDragging,
    likeOpacity,
    dislikeOpacity,
    triggerSwipe,
    reset,
    handlers: {
      mousedown:  onDragStart,
      mousemove:  onDragMove,
      mouseup:    onDragEnd,
      mouseleave: onDragEnd,
      touchstart: onDragStart,
      touchmove:  onDragMove,
      touchend:   onDragEnd,
    },
  }
}
