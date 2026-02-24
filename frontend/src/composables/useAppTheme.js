import { computed } from 'vue'
import { useTheme } from 'vuetify'

export function useAppTheme() {
  const theme = useTheme()

  const isDark = computed(() => theme.global.current.value.dark)

  function toggle() {
    const next = isDark.value ? 'healthmate' : 'healthmate-dark'
    theme.global.name.value = next
    localStorage.setItem('hm_theme', next)
  }

  return { isDark, toggle }
}
