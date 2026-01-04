<script setup>
import PublicTopBar from '@/components/layout/PublicTopBar.vue'
import PublicFooter from '@/components/layout/PublicFooter.vue'
</script>

<template>
  <v-app>
    <PublicTopBar />

    <v-main class="public-main">
      <router-view />
    </v-main>

    <PublicFooter />
  </v-app>
</template>

<style scoped>
.public-main {
  flex: 1 1 auto;
}
</style>
