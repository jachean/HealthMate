<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppTheme } from '@/composables/useAppTheme'
import AppLogo from '@/components/ui/AppLogo.vue'

defineProps({
  errorMessage: {
    type: String,
    default: null,
  },
})

const { t, locale } = useI18n()
const localeMenuOpen = ref(false)
const { isDark, toggle: toggleTheme } = useAppTheme()

function switchLocale(lang) {
  locale.value = lang
  localStorage.setItem('hm_locale', lang)
}
</script>

<template>
  <v-main class="auth-main">

    <!-- Top-right controls -->
    <div class="auth-locale-wrap">

      <!-- Dark mode toggle -->
      <v-btn
        icon
        size="small"
        class="locale-btn theme-btn"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
      >
        <v-icon size="17">{{ isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>

      <!-- Locale switcher -->
      <v-menu v-model="localeMenuOpen" location="bottom end">
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            variant="text"
            size="small"
            class="locale-btn"
            aria-label="Change language"
          >
            <span :class="['fi', locale === 'ro' ? 'fi-ro' : 'fi-gb', 'locale-flag']" />
            <span class="locale-code ml-1">{{ locale.toUpperCase() }}</span>
            <v-icon size="15" class="ml-1">mdi-chevron-down</v-icon>
          </v-btn>
        </template>

        <v-list density="compact" min-width="148">
          <v-list-item @click="switchLocale('en')" :active="locale === 'en'" color="primary">
            <v-list-item-title>
              <span class="fi fi-gb mr-2" />EN &mdash; English
            </v-list-item-title>
          </v-list-item>
          <v-list-item @click="switchLocale('ro')" :active="locale === 'ro'" color="primary">
            <v-list-item-title>
              <span class="fi fi-ro mr-2" />RO &mdash; Română
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>

    <v-container fluid class="fill-height d-flex align-center justify-center">
      <v-row justify="center" class="w-100 ma-0">
        <v-col cols="12" md="10" lg="8">

          <div class="app-title-wrapper">
            <AppLogo />
            <p class="app-tagline">Empowering your health journey</p>
          </div>

          <v-card class="auth-card" elevation="12">
            <slot />
            <p v-if="errorMessage" class="error">
              {{ errorMessage }}
            </p>
          </v-card>

          <slot name="desktop-tip" />

        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<style>
/* Dark mode overrides for auth locale/theme buttons (non-scoped for theme selector access) */
.v-theme--healthmate-dark .auth-locale-wrap .locale-btn {
  color: rgb(var(--v-theme-on-surface));
  background: rgba(26, 33, 56, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<style scoped>
/* ── Locale switcher ───────────────────────────────────────────────────────── */
.auth-locale-wrap {
  position: fixed;
  top: 16px;
  right: 20px;
  z-index: 100;
  display: flex;
  align-items: center;
  gap: 8px;
}

.theme-btn {
  width: 36px;
  height: 36px;
  min-width: unset;
  padding: 0 !important;
}

.locale-btn {
  text-transform: none;
  letter-spacing: normal;
  padding: 0 10px;
  color: #4a5568;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 8px !important;
}

.locale-code {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.locale-flag {
  width: 20px;
  height: 15px;
  border-radius: 2px;
  flex-shrink: 0;
}

/* ── Logo hover (mirrors navbar effect) ────────────────────────────────────── */
.app-title-wrapper {
  text-align: center;
  margin-bottom: 28px;
  transition: opacity 0.25s ease, transform 0.25s ease;
  cursor: default;
}

.app-title-wrapper:hover {
  opacity: 0.88;
  transform: translateY(-1px);
}

.app-title-wrapper:hover :deep(.app-dot) {
  box-shadow: 0 0 0 9px rgba(0, 230, 118, 0.2), 0 0 12px rgba(0, 230, 118, 0.4);
  transform: scale(1.15);
}

:deep(.app-dot) {
  transition: box-shadow 0.25s ease, transform 0.25s ease;
}
</style>
