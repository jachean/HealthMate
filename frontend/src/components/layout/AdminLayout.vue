<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppTheme } from '@/composables/useAppTheme'
import { useAuthStore } from '@/stores/auth'

const { t, locale } = useI18n()
const { isDark, toggle: toggleTheme } = useAppTheme()
const auth = useAuthStore()

const allNavItems = [
  { title: () => t('admin.nav.doctors'), icon: 'mdi-doctor', to: { name: 'admin-doctors' }, clinicAdminVisible: true },
  { title: () => t('admin.nav.clinics'), icon: 'mdi-hospital-building', to: { name: 'admin-clinics' }, clinicAdminVisible: true },
  { title: () => t('admin.nav.appointments'), icon: 'mdi-calendar-clock', to: { name: 'admin-appointments' }, clinicAdminVisible: true },
  { title: () => t('admin.nav.users'), icon: 'mdi-account-group', to: { name: 'admin-users' }, clinicAdminVisible: false },
  { title: () => t('admin.nav.content'), icon: 'mdi-database-edit', to: { name: 'admin-content' }, clinicAdminVisible: true },
  { title: () => t('admin.nav.analytics'), icon: 'mdi-chart-bar', to: { name: 'admin-analytics' }, clinicAdminVisible: true },
]

const navItems = computed(() =>
  auth.isClinicAdmin
    ? allNavItems.filter(item => item.clinicAdminVisible)
    : allNavItems
)

const localeMenuOpen = ref(false)

function switchLocale(lang) {
  locale.value = lang
  localStorage.setItem('hm_locale', lang)
}
</script>

<template>
  <v-app-bar flat elevate-on-scroll scroll-behavior="elevate" scroll-threshold="16" height="56">
    <template #prepend>
      <v-icon size="20" color="primary" class="ml-3 mr-1">mdi-shield-crown</v-icon>
    </template>
    <v-app-bar-title class="text-body-2 font-weight-medium text-medium-emphasis">
      {{ t('admin.nav.panel') }}
    </v-app-bar-title>

    <template #append>
      <!-- Dark mode toggle -->
      <v-btn icon variant="text" size="small" @click="toggleTheme">
        <v-icon size="20">{{ isDark ? 'mdi-white-balance-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>

      <!-- Locale switcher -->
      <v-menu v-model="localeMenuOpen" location="bottom end">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" size="small" class="locale-btn mr-2">
            <span :class="['fi', locale === 'ro' ? 'fi-ro' : 'fi-gb', 'locale-flag']"></span>
            <span class="text-caption font-weight-medium ml-1">{{ locale.toUpperCase() }}</span>
            <v-icon size="16" class="ml-1">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list density="compact" min-width="140">
          <v-list-item @click="switchLocale('en')" :active="locale === 'en'" color="primary">
            <v-list-item-title><span class="fi fi-gb mr-2"></span>EN &mdash; English</v-list-item-title>
          </v-list-item>
          <v-list-item @click="switchLocale('ro')" :active="locale === 'ro'" color="primary">
            <v-list-item-title><span class="fi fi-ro mr-2"></span>RO &mdash; Română</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </template>
  </v-app-bar>

  <v-navigation-drawer permanent width="240">
    <v-list density="compact" nav class="mt-2">
      <v-list-item
        v-for="item in navItems"
        :key="item.to.name"
        :prepend-icon="item.icon"
        :title="item.title()"
        :to="item.to"
        color="primary"
      />
    </v-list>

    <v-divider class="my-2" />

    <v-list density="compact" nav>
      <v-list-item
        prepend-icon="mdi-arrow-left"
        :title="t('admin.nav.backToSite')"
        :to="{ name: 'home' }"
      />
    </v-list>
  </v-navigation-drawer>

  <v-main>
    <v-container fluid class="pa-6">
      <router-view />
    </v-container>
  </v-main>
</template>

<style scoped>
.locale-btn {
  text-transform: none;
  letter-spacing: normal;
  min-width: auto;
  padding: 0 8px;
}

.locale-flag {
  width: 20px;
  height: 15px;
  border-radius: 2px;
  flex-shrink: 0;
}
</style>
