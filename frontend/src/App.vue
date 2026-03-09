<script setup>
import { useToastStore } from '@/stores/toast'
const toast = useToastStore()
</script>

<template>
  <v-app>
    <RouterView />
    <v-snackbar
      v-model="toast.visible"
      :color="toast.color"
      location="bottom right"
      rounded="lg"
      :timeout="3000"
      elevation="4"
    >
      <div class="d-flex align-center ga-2">
        <v-icon size="18">mdi-check-circle-outline</v-icon>
        {{ toast.message }}
      </div>
    </v-snackbar>
  </v-app>
</template>
