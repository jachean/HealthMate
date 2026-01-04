<script setup></script>

<template>
  <footer class="footer px-6">
    <v-container>
      <div class="footer-top">
        <div class="footer-copy">
          <div class="footer-title">
            Medical appointment booking, simplified
          </div>
          <div class="footer-subtitle text-body-2 text-medium-emphasis">
            Browse clinics and doctors, book appointments, and manage your visits in one place.
          </div>
        </div>

        <div class="footer-links text-body-2 text-medium-emphasis">
          <RouterLink to="/terms">Terms</RouterLink>
          <RouterLink to="/privacy">Privacy</RouterLink>
          <RouterLink to="/contact">Contact</RouterLink>
        </div>
      </div>

      <v-divider class="my-4" />

      <div class="footer-bottom text-body-2 text-medium-emphasis">
        <span>© {{ new Date().getFullYear() }} HealthMate</span>
        <span class="footer-tagline">Clear, modern medical booking</span>
      </div>
    </v-container>
  </footer>
</template>

<style scoped>
</style>
