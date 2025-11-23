import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { healthTips } from '@/config/healthTips'

export default function useHealthTips() {
  const currentTipIndex = ref(0)
  const currentTip = computed(() => healthTips[currentTipIndex.value])

  let intervalId = null

  onMounted(() => {
    intervalId = setInterval(() => {
      currentTipIndex.value = (currentTipIndex.value + 1) % healthTips.length
    }, 10000)
  })

  onBeforeUnmount(() => {
    if (intervalId) clearInterval(intervalId)
  })

  return { currentTip, currentTipIndex }
}
