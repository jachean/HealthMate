<script setup>
defineProps({
  errorMessage: String
})
</script>

<template>
  <v-main class="auth-main">
    <v-container fluid class="fill-height d-flex align-center justify-center">
      <v-row justify="center" class="w-100 ma-0">
        <v-col cols="12" md="10" lg="8">

          <div class="app-title-wrapper">
            <h1 class="app-title">
              <span class="app-dot"></span>
              <span>HEALTHMATE</span>
            </h1>
            <p class="app-tagline">Empowering your health journey</p>
          </div>

          <v-card class="auth-card" elevation="12">
            <slot />
            <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
          </v-card>

          <slot name="desktop-tip" />

        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>
