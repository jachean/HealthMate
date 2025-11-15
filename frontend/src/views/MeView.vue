<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="me">
    <h1>My Profile</h1>
    <pre v-if="auth.user">{{ auth.user }}</pre>
    <p v-else>Loading...</p>

    <button @click="auth.logout(); $router.push('/login')">Logout</button>
  </div>
</template>

<style scoped>
.me {
  max-width: 600px;
  margin: 2rem auto;
  display: grid;
  gap: 1rem;
}
pre {
  background: #f7f7f7;
  padding: 1rem;
  border-radius: 8px;
  overflow: auto;
}
button {
  width: fit-content;
  padding: 0.6rem 1rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}
</style>
