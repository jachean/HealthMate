<script setup>
import AppLogo from '@/components/ui/AppLogo.vue'

defineProps({
  errorMessage: {
    type: String,
    default: null,
  },
})
</script>

<template>
  <v-main class="auth-main">
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
