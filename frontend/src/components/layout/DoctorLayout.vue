<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { uploadUrl } from '@/utils/url'

const { t } = useI18n()
const auth = useAuthStore()

const initials = computed(() => {
  const f = auth.user?.firstName || ''
  const l = auth.user?.lastName || ''
  return (f.charAt(0) + l.charAt(0)).toUpperCase()
})

const navItems = [
  { title: () => t('doctor.nav.dashboard'), icon: 'mdi-calendar-month', to: { name: 'doctor-dashboard' } },
]
</script>

<template>
  <v-app-bar flat elevate-on-scroll scroll-behavior="elevate" scroll-threshold="16" height="56">
    <template #prepend>
      <v-icon size="20" color="teal" class="ml-3 mr-1">mdi-stethoscope</v-icon>
    </template>
    <v-app-bar-title class="text-body-2 font-weight-medium text-medium-emphasis">
      {{ t('doctor.nav.panel') }}
    </v-app-bar-title>
  </v-app-bar>

  <v-navigation-drawer permanent width="240">
    <v-list density="compact" nav class="mt-2">
      <v-list-item
        v-for="item in navItems"
        :key="item.to.name"
        :prepend-icon="item.icon"
        :title="item.title()"
        :to="item.to"
        color="teal"
      />
    </v-list>

    <v-divider class="my-2" />

    <v-list density="compact" nav>
      <v-list-item
        prepend-icon="mdi-arrow-left"
        :title="t('doctor.nav.backToSite')"
        :to="{ name: 'home' }"
      />
    </v-list>

    <template #append>
      <v-divider />
      <div class="pa-3 d-flex align-center ga-3">
        <v-avatar size="34" color="teal" class="text-white font-weight-medium flex-shrink-0">
          <v-img v-if="auth.user?.profileImage" :src="uploadUrl(auth.user.profileImage)" cover />
          <span v-else style="font-size:13px">{{ initials }}</span>
        </v-avatar>
        <div style="min-width:0">
          <div class="text-body-2 font-weight-medium text-truncate">
            {{ auth.user?.firstName }} {{ auth.user?.lastName }}
          </div>
          <div class="text-caption text-medium-emphasis text-truncate">{{ auth.user?.email }}</div>
        </div>
      </div>
    </template>
  </v-navigation-drawer>

  <v-main>
    <v-container fluid class="pa-6">
      <router-view />
    </v-container>
  </v-main>
</template>
