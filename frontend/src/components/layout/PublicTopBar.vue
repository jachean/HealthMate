<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/ui/AppLogo.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { t, locale } = useI18n()
const isAuthenticated = computed(() => !!auth.token)
const displayFirstName = computed(() => auth.user?.firstName || 'HealthMate')
const displayLastName = computed(() => auth.user?.lastName || 'User')
const displayUsername = computed(() => auth.user?.username || 'user')

const initials = computed(() => {
  return (
    displayFirstName.value.charAt(0) +
    displayLastName.value.charAt(0)
  ).toUpperCase()
})

const navLinks = computed(() => [
  { label: t('nav.home'), to: { name: 'home' }, icon: 'mdi-home-outline', activeIcon: 'mdi-home' },
  { label: t('nav.doctors'), to: { name: 'doctors' }, icon: 'mdi-stethoscope', activeIcon: 'mdi-stethoscope' },
])

function isActive(link) {
  return route.name === link.to.name
}

const mobileDrawer = ref(false)

const logout = () => {
  auth.logout()
  mobileDrawer.value = false
  router.push({ name: 'home' })
}

function switchLocale(lang) {
  locale.value = lang
  localStorage.setItem('hm_locale', lang)
}

const localeMenuOpen = ref(false)
</script>

<template>
  <v-app-bar
    flat
    elevate-on-scroll
    scroll-behavior="elevate"
    scroll-threshold="16"
    height="64"
    class="px-6"
  >
    <v-container class="toolbar-container">
      <RouterLink to="/" class="logo-link text-decoration-none" style="justify-self: start; width: fit-content;">
        <AppLogo />
      </RouterLink>

      <!-- Desktop nav (hidden on mobile) -->
      <nav class="nav-links d-none d-md-flex">
        <v-btn
          v-for="link in navLinks"
          :key="link.label"
          variant="text"
          :to="link.to"
          :class="['nav-link', { 'nav-link--active': isActive(link) }]"
          :ripple="false"
        >
          <v-icon start size="18">{{ isActive(link) ? link.activeIcon : link.icon }}</v-icon>
          {{ link.label }}
        </v-btn>
      </nav>

      <!-- Desktop auth + locale (hidden on mobile) -->
      <div class="toolbar-right d-none d-md-flex">
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
              <span :class="['fi', locale === 'ro' ? 'fi-ro' : 'fi-gb', 'locale-flag']"></span>
              <span class="text-caption font-weight-medium ml-1">{{ locale.toUpperCase() }}</span>
              <v-icon size="16" class="ml-1">mdi-chevron-down</v-icon>
            </v-btn>
          </template>

          <v-list density="compact" min-width="140">
            <v-list-item
              @click="switchLocale('en')"
              :active="locale === 'en'"
              color="primary"
            >
              <v-list-item-title><span class="fi fi-gb mr-2"></span>EN &mdash; English</v-list-item-title>
            </v-list-item>
            <v-list-item
              @click="switchLocale('ro')"
              :active="locale === 'ro'"
              color="primary"
            >
              <v-list-item-title><span class="fi fi-ro mr-2"></span>RO &mdash; Română</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>

        <template v-if="!isAuthenticated">
          <v-btn variant="text" to="/login">
            {{ t('nav.login') }}
          </v-btn>
          <v-btn color="primary" class="ml-2" to="/register">
            {{ t('nav.createAccount') }}
          </v-btn>
        </template>

        <template v-else>
          <v-menu location="bottom end">
            <template #activator="{ props }">
              <v-btn
                v-bind="props"
                variant="text"
                class="px-1"
                aria-label="Account menu"
              >
                <v-avatar
                  size="36"
                  color="primary"
                  class="text-white font-weight-medium"
                >
                  {{ initials }}
                </v-avatar>
              </v-btn>
            </template>

            <v-list density="compact" min-width="220">
              <v-list-item>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ displayFirstName }} {{ displayLastName }}
                  </span>
                  <span class="text-body-2 text-medium-emphasis">
                    @{{ displayUsername }}
                  </span>
                </div>
              </v-list-item>

              <v-divider class="my-1" />

              <v-list-item
                :title="t('nav.myProfile')"
                :to="{ name: 'me' }"
              />
              <v-list-item
                :title="t('nav.logout')"
                @click="logout"
              />
            </v-list>
          </v-menu>
        </template>
      </div>

      <!-- Mobile hamburger (hidden on desktop) -->
      <div class="mobile-menu-btn d-flex d-md-none">
        <v-btn
          icon
          variant="text"
          aria-label="Menu"
          @click="mobileDrawer = !mobileDrawer"
        >
          <v-icon>{{ mobileDrawer ? 'mdi-close' : 'mdi-menu' }}</v-icon>
        </v-btn>
      </div>
    </v-container>
  </v-app-bar>

  <!-- Mobile drawer -->
  <v-navigation-drawer
    v-model="mobileDrawer"
    location="right"
    temporary
    width="280"
    class="d-md-none"
  >
    <div class="mobile-drawer-content">
      <!-- User info (if authenticated) -->
      <div v-if="isAuthenticated" class="mobile-user-section pa-4">
        <div class="d-flex align-center ga-3">
          <v-avatar size="40" color="primary" class="text-white font-weight-medium">
            {{ initials }}
          </v-avatar>
          <div>
            <div class="text-subtitle-2 font-weight-bold">
              {{ displayFirstName }} {{ displayLastName }}
            </div>
            <div class="text-caption text-medium-emphasis">
              @{{ displayUsername }}
            </div>
          </div>
        </div>
      </div>

      <v-divider v-if="isAuthenticated" />

      <!-- Nav links -->
      <v-list density="comfortable" nav>
        <v-list-item
          v-for="link in navLinks"
          :key="link.label"
          :to="link.to"
          :prepend-icon="isActive(link) ? link.activeIcon : link.icon"
          :active="isActive(link)"
          color="primary"
          @click="mobileDrawer = false"
        >
          <v-list-item-title class="font-weight-medium">{{ link.label }}</v-list-item-title>
        </v-list-item>
      </v-list>

      <v-divider />

      <!-- Mobile locale switcher -->
      <div class="pa-4 pb-2">
        <div class="text-caption text-medium-emphasis mb-2">Language</div>
        <v-btn-toggle
          :model-value="locale"
          @update:model-value="switchLocale($event)"
          mandatory
          color="primary"
          density="compact"
          class="w-100"
        >
          <v-btn value="en" class="flex-grow-1"><span class="fi fi-gb mr-2"></span>EN</v-btn>
          <v-btn value="ro" class="flex-grow-1"><span class="fi fi-ro mr-2"></span>RO</v-btn>
        </v-btn-toggle>
      </div>

      <v-divider />

      <!-- Auth actions -->
      <div class="pa-4">
        <template v-if="!isAuthenticated">
          <v-btn
            variant="outlined"
            block
            to="/login"
            class="mb-2"
            @click="mobileDrawer = false"
          >
            {{ t('nav.login') }}
          </v-btn>
          <v-btn
            color="primary"
            block
            to="/register"
            @click="mobileDrawer = false"
          >
            {{ t('nav.createAccount') }}
          </v-btn>
        </template>

        <template v-else>
          <v-btn
            variant="outlined"
            block
            :to="{ name: 'me' }"
            class="mb-2"
            prepend-icon="mdi-account-outline"
            @click="mobileDrawer = false"
          >
            {{ t('nav.myProfile') }}
          </v-btn>
          <v-btn
            variant="text"
            block
            color="error"
            prepend-icon="mdi-logout"
            @click="logout"
          >
            {{ t('nav.logout') }}
          </v-btn>
        </template>
      </div>
    </div>
  </v-navigation-drawer>
</template>

<style scoped>
.toolbar-container {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
}

.toolbar-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.nav-links {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.nav-link {
  position: relative;
  text-transform: none;
  letter-spacing: normal;
  font-weight: 500;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.nav-link:hover {
  opacity: 1;
}

.nav-link--active {
  opacity: 1;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

.nav-link--active::after {
  content: '';
  position: absolute;
  bottom: 4px;
  left: 16px;
  right: 16px;
  height: 2px;
  border-radius: 1px;
  background: rgb(var(--v-theme-primary));
}

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

.logo-link {
  background-color: transparent !important;
  padding: 0 !important;
  border-radius: 0 !important;
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.logo-link:hover {
  background-color: transparent !important;
  opacity: 0.85;
  transform: translateY(-1px);
}

.logo-link:hover :deep(.app-dot) {
  box-shadow: 0 0 0 9px rgba(0, 230, 118, 0.2), 0 0 12px rgba(0, 230, 118, 0.4);
  transform: scale(1.15);
  transition: box-shadow 0.25s ease, transform 0.25s ease;
}

:deep(.app-dot) {
  transition: box-shadow 0.25s ease, transform 0.25s ease;
}

.mobile-user-section {
  background: rgba(var(--v-theme-primary), 0.04);
}

@media (max-width: 959px) {
  .toolbar-container {
    grid-template-columns: auto 1fr auto;
  }

  .toolbar-container > a {
    grid-column: 2;
    justify-self: center;
  }

  .mobile-menu-btn {
    grid-column: 3;
    justify-self: end;
  }
}
</style>
