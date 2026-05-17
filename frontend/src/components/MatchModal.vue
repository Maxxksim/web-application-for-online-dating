<template>
  <div class="fixed inset-0 z-[200] flex flex-col pt-12 text-white bg-[#09050f]/95">
    <!-- Starburst bg -->
    <div class="absolute inset-0 z-0 bg-no-repeat bg-center bg-cover opacity-70" :style="{ backgroundImage: 'radial-gradient(circle, rgba(139,92,246,0.5) 0%, transparent 55%)' }"></div>

    <div class="relative z-10 flex flex-col items-center flex-1 px-6 pt-[10vh] animate-match-reveal text-center">
      <div class="flex flex-col mb-12">
        <p class="text-sm font-semibold tracking-[0.35em] text-rose-300 uppercase mb-2">Mutual Like</p>
        <h2 class="text-4xl font-extrabold pb-2 bg-gradient-to-r from-violet-300 to-rose-300 bg-clip-text text-transparent italic">It's a Match!</h2>
      </div>

      <div class="flex items-center gap-4 mb-16 relative w-full justify-center">
        <div class="w-28 h-28 rounded-full border-4 border-white/80 overflow-hidden shadow-[0_0_20px_var(--color-violet-500)] z-10">
          <img v-if="myPhoto" :src="myPhoto" alt="You" class="w-full h-full object-cover text-xs text-white/50 bg-white/10 flex items-center justify-center indent-[100px]" />
          <span v-else class="w-full h-full flex items-center justify-center bg-white/10 text-3xl font-bold">Me</span>
        </div>
        
        <div class="w-28 h-28 rounded-full border-4 border-white/80 overflow-hidden shadow-[0_0_20px_var(--color-rose-500)] z-10">
          <img v-if="theirPhoto" :src="theirPhoto" alt="Match" class="w-full h-full object-cover text-xs text-white/50 bg-white/10 flex items-center justify-center indent-[100px]" />
          <span v-else class="w-full h-full flex items-center justify-center bg-white/10 text-3xl font-bold">{{ theirName[0] }}</span>
        </div>
      </div>

      <div class="mb-auto">
        <p class="text-lg text-white/90 font-medium">You liked <strong class="font-extrabold text-white">{{ theirName }}</strong></p>
      </div>

      <div class="w-full max-w-sm pb-10 flex flex-col gap-4 text-center">
        <button class="w-full py-4 rounded-full font-bold uppercase tracking-wider transition-transform bg-white/10 text-white border border-white/30 hover:bg-white/20 active:scale-95" @click="close">Continue</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  matchData: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const theirName = computed(() => props.matchData.user.name || 'User')
const theirPhoto = computed(() => props.matchData.user.photos?.[0]?.url || props.matchData.user.photo_url)
const myPhoto = computed(() => props.matchData.match?.photos?.[0]?.url || null)

const close = () => {
  emit('close')
}
</script>
<style scoped>
@keyframes match-reveal {
  0% { opacity: 0; transform: scale(0.9); }
  60% { opacity: 1; transform: scale(1.05); }
  100% { opacity: 1; transform: scale(1); }
}
.animate-match-reveal {
  animation: match-reveal 0.5s ease-out forwards;
}
</style>
