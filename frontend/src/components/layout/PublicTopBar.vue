<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/ui/AppLogo.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const isAuthenticated = computed(() => !!auth.token)
const isHomeActive = computed(() => route.name === 'home')
const displayFirstName = computed(() => auth.user?.firstName || 'HealthMate')
const displayLastName = computed(() => auth.user?.lastName || 'User')
const displayUsername = computed(() => auth.user?.username || 'user')

const initials = computed(() => {
  return (
    displayFirstName.value.charAt(0) +
    displayLastName.value.charAt(0)
  ).toUpperCase()
})

const logout = () => {
  auth.logout()
  router.push({ name: 'home' })
}
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
    <v-container class="d-flex align-center">
      <RouterLink to="/" class="text-decoration-none">
        <AppLogo />
      </RouterLink>

      <v-spacer />

      <v-btn
        variant="text"
        :to="{ name: 'home' }"
        :class="{ 'nav-active': isHomeActive }"
      >
        Home
      </v-btn>

      <v-spacer />

      <template v-if="!isAuthenticated">
        <v-btn variant="text" to="/login">
          Log in
        </v-btn>
        <v-btn color="primary" class="ml-2" to="/register">
          Create account
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
              title="My profile"
              :to="{ name: 'me' }"
            />
            <v-list-item
              title="Log out"
              @click="logout"
            />
          </v-list>
        </v-menu>
      </template>
    </v-container>
  </v-app-bar>
</template>
