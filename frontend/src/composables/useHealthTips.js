import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

export default function useHealthTips() {
  const { tm } = useI18n()

  const currentTipIndex = ref(0)
  const tips = computed(() => tm('healthTips'))
  const currentTip = computed(() => tips.value[currentTipIndex.value] ?? '')

  let intervalId = null

  onMounted(() => {
    intervalId = setInterval(() => {
      currentTipIndex.value = (currentTipIndex.value + 1) % tips.value.length
    }, 10000)
  })

  onBeforeUnmount(() => {
    if (intervalId) clearInterval(intervalId)
  })

  return { currentTip, currentTipIndex }
}
